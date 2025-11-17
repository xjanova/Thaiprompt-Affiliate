@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า MLM')

@section('content')
<div x-data="mlmSettings()" x-init="init()" class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-300"></div>
        </div>

        <div class="relative z-10">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-3 flex items-center gap-3">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <i class="fas fa-cog text-white text-2xl"></i>
                        </div>
                        ตั้งค่า MLM System
                    </h1>
                    <p class="text-white/90 text-lg">Premium Edition - ตั้งค่าระบบ MLM แบบครบวงจร พร้อมการคำนวณ Real-time</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.mlm.calculator') }}"
                       class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl shadow-lg transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-calculator"></i>
                        เครื่องคำนวณ
                    </a>
                    <button @click="saveSettings()"
                            :disabled="saving"
                            class="px-8 py-3 bg-white hover:bg-gray-100 text-purple-600 font-bold rounded-xl shadow-lg transition-all duration-200 flex items-center gap-2 disabled:opacity-50">
                        <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                        <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Overpay Warning Alert --}}
    <div x-show="totalCommissionPercentage > 0"
         x-transition
         class="rounded-2xl p-6 border-2 shadow-xl"
         :class="{
             'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700': totalCommissionPercentage > 50,
             'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
             'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700': totalCommissionPercentage <= 40
         }">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                     :class="{
                         'bg-red-500': totalCommissionPercentage > 50,
                         'bg-yellow-500': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
                         'bg-green-500': totalCommissionPercentage <= 40
                     }">
                    <i class="fas text-white text-xl"
                       :class="{
                           'fa-exclamation-triangle': totalCommissionPercentage > 50,
                           'fa-exclamation-circle': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
                           'fa-check-circle': totalCommissionPercentage <= 40
                       }"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold mb-2"
                    :class="{
                        'text-red-800 dark:text-red-300': totalCommissionPercentage > 50,
                        'text-yellow-800 dark:text-yellow-300': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
                        'text-green-800 dark:text-green-300': totalCommissionPercentage <= 40
                    }">
                    <span x-text="`ประมาณการคอมมิชชั่นรวม: ${totalCommissionPercentage.toFixed(2)}%`"></span>
                </h3>
                <div class="space-y-2 text-sm"
                     :class="{
                         'text-red-700 dark:text-red-400': totalCommissionPercentage > 50,
                         'text-yellow-700 dark:text-yellow-400': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
                         'text-green-700 dark:text-green-400': totalCommissionPercentage <= 40
                     }">
                    <template x-if="totalCommissionPercentage > 50">
                        <div>
                            <strong class="flex items-center gap-2 mb-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                อันตราย - Overpay! คอมมิชชั่นรวมเกิน 50%
                            </strong>
                            <p>การตั้งค่าปัจจุบันอาจทำให้ธุรกิจขาดทุน กรุณาลดอัตราคอมมิชชั่น</p>
                            <div class="mt-3 p-3 bg-white/50 dark:bg-black/20 rounded-lg">
                                <p class="font-medium mb-1">คำแนะนำ:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>ลดเปอร์เซ็นต์ Binary Commission</li>
                                    <li>ลดจำนวนชั้น Unilevel</li>
                                    <li>ลดเปอร์เซ็นต์ Direct Referral</li>
                                </ul>
                            </div>
                        </div>
                    </template>
                    <template x-if="totalCommissionPercentage > 40 && totalCommissionPercentage <= 50">
                        <div>
                            <strong class="flex items-center gap-2 mb-2">
                                <i class="fas fa-exclamation-circle"></i>
                                คำเตือน - คอมมิชชั่นใกล้ขีดจำกัด
                            </strong>
                            <p>เปอร์เซ็นต์คอมมิชชั่นอยู่ในระดับสูง กรุณาตรวจสอบการคำนวณ</p>
                        </div>
                    </template>
                    <template x-if="totalCommissionPercentage <= 40">
                        <div>
                            <strong class="flex items-center gap-2 mb-2">
                                <i class="fas fa-check-circle"></i>
                                ปลอดภัย - เปอร์เซ็นต์เหมาะสม
                            </strong>
                            <p>การตั้งค่าอยู่ในเกณฑ์ที่แนะนำ ธุรกิจมีกำไรเหลือเพียงพอ</p>
                        </div>
                    </template>
                </div>

                {{-- Breakdown --}}
                <div class="mt-4 p-4 bg-white/50 dark:bg-black/20 rounded-xl">
                    <p class="font-semibold mb-2">รายละเอียดการคำนวณ:</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <p class="opacity-75">Binary</p>
                            <p class="font-bold text-base" x-text="`${(settings.binary_match_commission || 0)}%`"></p>
                        </div>
                        <div>
                            <p class="opacity-75">Unilevel</p>
                            <p class="font-bold text-base" x-text="`${unilevelTotal.toFixed(2)}%`"></p>
                        </div>
                        <div>
                            <p class="opacity-75">Direct</p>
                            <p class="font-bold text-base" x-text="`${(settings.direct_referral_commission || 0)}%`"></p>
                        </div>
                        <div>
                            <p class="opacity-75">รวมทั้งหมด</p>
                            <p class="font-bold text-lg" x-text="`${totalCommissionPercentage.toFixed(2)}%`"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left Column: Main Settings (2/3) --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Binary System Settings --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-sitemap"></i>
                        Binary System
                    </h2>
                    <p class="text-sm text-white/80 mt-1">ระบบโครงสร้างทวิภาคี (Binary Tree)</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Binary Enabled Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งาน Binary</label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">เปิด/ปิด ระบบคอมมิชชั่น Binary Tree</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.binary_enabled" @change="calculateTotal()" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-pink-600"></div>
                        </label>
                    </div>

                    {{-- Binary Match Commission --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Binary Match Commission (%)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.binary_match_commission"
                                   @input="calculateTotal()"
                                   min="0" max="30" step="0.5"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                   :disabled="!settings.binary_enabled">
                            <div class="w-20 px-3 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-center font-bold">
                                <span x-text="(settings.binary_match_commission || 0) + '%'"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">คอมมิชชั่นจากการจับคู่ขา Binary (แนะนำ: 5-15%)</p>
                    </div>

                    {{-- Auto Placement Strategy --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                            กลยุทธ์การจัดเรียงอัตโนมัติ
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button"
                                    @click="settings.auto_placement_type = 'left_to_right'"
                                    :disabled="!settings.auto_placement"
                                    class="p-4 rounded-xl border-2 transition-all text-left"
                                    :class="settings.auto_placement_type === 'left_to_right'
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-arrow-right text-2xl" :class="settings.auto_placement_type === 'left_to_right' ? 'text-purple-600' : 'text-gray-400'"></i>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Left to Right</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">เรียงซ้ายไปขวา</p>
                                    </div>
                                </div>
                            </button>

                            <button type="button"
                                    @click="settings.auto_placement_type = 'balanced'"
                                    :disabled="!settings.auto_placement"
                                    class="p-4 rounded-xl border-2 transition-all text-left"
                                    :class="settings.auto_placement_type === 'balanced'
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-balance-scale text-2xl" :class="settings.auto_placement_type === 'balanced' ? 'text-purple-600' : 'text-gray-400'"></i>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Balanced</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">กระจายเท่าๆ กัน</p>
                                    </div>
                                </div>
                            </button>

                            <button type="button"
                                    @click="settings.auto_placement_type = 'weak_leg'"
                                    :disabled="!settings.auto_placement"
                                    class="p-4 rounded-xl border-2 transition-all text-left"
                                    :class="settings.auto_placement_type === 'weak_leg'
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-chart-line text-2xl" :class="settings.auto_placement_type === 'weak_leg' ? 'text-purple-600' : 'text-gray-400'"></i>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Weak Leg</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">เติมขาที่อ่อน</p>
                                    </div>
                                </div>
                            </button>

                            <button type="button"
                                    @click="settings.auto_placement_type = 'fill_by_level'"
                                    :disabled="!settings.auto_placement"
                                    class="p-4 rounded-xl border-2 transition-all text-left"
                                    :class="settings.auto_placement_type === 'fill_by_level'
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-layer-group text-2xl" :class="settings.auto_placement_type === 'fill_by_level' ? 'text-purple-600' : 'text-gray-400'"></i>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Fill by Level</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">เติมให้เต็มชั้น</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Auto Placement Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-900 dark:text-white">Auto Placement</label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">จัดเรียงสมาชิกใหม่อัตโนมัติ</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.auto_placement" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-pink-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Unilevel System Settings --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-layer-group"></i>
                        Unilevel System
                    </h2>
                    <p class="text-sm text-white/80 mt-1">ระบบคอมมิชชั่นหลายระดับ (Multi-Level)</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Unilevel Enabled Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งาน Unilevel</label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">เปิด/ปิด ระบบคอมมิชชั่น Unilevel</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.unilevel_enabled" @change="calculateTotal()" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-600 peer-checked:to-emerald-600"></div>
                        </label>
                    </div>

                    {{-- Unilevel Levels --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            จำนวนชั้น Unilevel
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.unilevel_levels"
                                   @input="updateUnilevelPercentages(); calculateTotal()"
                                   min="1" max="10" step="1"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                   :disabled="!settings.unilevel_enabled">
                            <div class="w-20 px-3 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-center font-bold">
                                <span x-text="settings.unilevel_levels + ' ชั้น'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Unilevel Percentages --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                            เปอร์เซ็นต์คอมมิชชั่นแต่ละชั้น
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <template x-for="level in settings.unilevel_levels" :key="level">
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                    <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block" x-text="`ชั้น ${level}`"></label>
                                    <div class="flex items-center gap-2">
                                        <input type="number"
                                               x-model.number="unilevelPercentages[level - 1]"
                                               @input="calculateTotal()"
                                               min="0" max="20" step="0.5"
                                               :disabled="!settings.unilevel_enabled"
                                               class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">%</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            รวม Unilevel: <strong x-text="unilevelTotal.toFixed(2) + '%'"></strong>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Direct Referral & Other Settings --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-user-plus"></i>
                        Direct Referral & Other
                    </h2>
                    <p class="text-sm text-white/80 mt-1">การตั้งค่าอื่นๆ</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Direct Referral Commission --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Direct Referral Commission (%)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.direct_referral_commission"
                                   @input="calculateTotal()"
                                   min="0" max="20" step="0.5"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <div class="w-20 px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-center font-bold">
                                <span x-text="(settings.direct_referral_commission || 0) + '%'"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">คอมมิชชั่นจากการแนะนำโดยตรง (แนะนำ: 5-10%)</p>
                    </div>

                    {{-- Minimum PV for Commission --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            PV ขั้นต่ำเพื่อรับคอมมิชชั่น
                        </label>
                        <input type="number"
                               x-model.number="settings.min_pv_for_commission"
                               min="0" step="10"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">สมาชิกต้องมี PV ขั้นต่ำเท่านี้เพื่อรับคอมมิชชั่น</p>
                    </div>

                    {{-- Matching Bonus --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Matching Bonus (%)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.matching_bonus"
                                   @input="calculateTotal()"
                                   min="0" max="10" step="0.5"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <div class="w-20 px-3 py-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-lg text-center font-bold">
                                <span x-text="(settings.matching_bonus || 0) + '%'"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">โบนัสจากทีมงาน (Matching Bonus)</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Preview & Quick Actions (1/3) --}}
        <div class="space-y-6">
            {{-- Commission Preview --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700 sticky top-6">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-chart-pie"></i>
                        ภาพรวมคอมมิชชั่น
                    </h3>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Total Percentage --}}
                    <div class="text-center p-6 rounded-xl"
                         :class="{
                             'bg-red-100 dark:bg-red-900/20': totalCommissionPercentage > 50,
                             'bg-yellow-100 dark:bg-yellow-900/20': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
                             'bg-green-100 dark:bg-green-900/20': totalCommissionPercentage <= 40
                         }">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">เปอร์เซ็นต์รวมทั้งหมด</p>
                        <p class="text-5xl font-bold"
                           :class="{
                               'text-red-600': totalCommissionPercentage > 50,
                               'text-yellow-600': totalCommissionPercentage > 40 && totalCommissionPercentage <= 50,
                               'text-green-600': totalCommissionPercentage <= 40
                           }"
                           x-text="totalCommissionPercentage.toFixed(2) + '%'"></p>
                    </div>

                    {{-- Breakdown --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Binary</span>
                            <span class="font-bold text-purple-600" x-text="(settings.binary_enabled ? settings.binary_match_commission : 0) + '%'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Unilevel</span>
                            <span class="font-bold text-green-600" x-text="(settings.unilevel_enabled ? unilevelTotal : 0).toFixed(2) + '%'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Direct Referral</span>
                            <span class="font-bold text-blue-600" x-text="(settings.direct_referral_commission || 0) + '%'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Matching Bonus</span>
                            <span class="font-bold text-cyan-600" x-text="(settings.matching_bonus || 0) + '%'"></span>
                        </div>
                    </div>

                    {{-- Profit Margin --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">กำไรคงเหลือ</span>
                            <span class="text-2xl font-bold"
                                  :class="{
                                      'text-red-600': (100 - totalCommissionPercentage) < 20,
                                      'text-yellow-600': (100 - totalCommissionPercentage) >= 20 && (100 - totalCommissionPercentage) < 40,
                                      'text-green-600': (100 - totalCommissionPercentage) >= 40
                                  }"
                                  x-text="(100 - totalCommissionPercentage).toFixed(2) + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all"
                                 :class="{
                                     'bg-red-600': (100 - totalCommissionPercentage) < 20,
                                     'bg-yellow-600': (100 - totalCommissionPercentage) >= 20 && (100 - totalCommissionPercentage) < 40,
                                     'bg-green-600': (100 - totalCommissionPercentage) >= 40
                                 }"
                                 :style="`width: ${Math.max(0, 100 - totalCommissionPercentage)}%`"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-500"></i>
                    การดำเนินการด่วน
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.mlm.members.index') }}"
                       class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-xl transition-all">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">จัดการสมาชิก</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">ดูและจัดการสมาชิก MLM</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.mlm.reports.dashboard') }}"
                       class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-xl transition-all">
                        <i class="fas fa-chart-pie text-green-600 text-xl"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">Dashboard</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">ดูภาพรวมระบบ MLM</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.mlm.calculator') }}"
                       class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-xl transition-all">
                        <i class="fas fa-calculator text-purple-600 text-xl"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">เครื่องคำนวณ</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">คำนวณคอมมิชชั่น</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mlmSettings() {
    return {
        settings: {
            binary_enabled: true,
            binary_match_commission: 10,
            auto_placement_type: 'balanced',
            auto_placement: true,
            unilevel_enabled: true,
            unilevel_levels: 5,
            direct_referral_commission: 5,
            min_pv_for_commission: 100,
            matching_bonus: 3,
        },
        unilevelPercentages: [5, 3, 2, 1, 1],
        saving: false,
        totalCommissionPercentage: 0,
        unilevelTotal: 0,

        init() {
            this.loadSettings();
            this.calculateTotal();
        },

        async loadSettings() {
            try {
                const response = await fetch('/admin/mlm/settings/get');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.settings) {
                        this.settings = {
                            binary_enabled: data.settings.binary_enabled ?? true,
                            binary_match_commission: parseFloat(data.settings.binary_match_commission) || 10,
                            auto_placement_type: data.settings.auto_placement_type || 'balanced',
                            auto_placement: data.settings.auto_placement ?? true,
                            unilevel_enabled: data.settings.unilevel_enabled ?? true,
                            unilevel_levels: parseInt(data.settings.unilevel_levels) || 5,
                            direct_referral_commission: parseFloat(data.settings.direct_referral_commission) || 5,
                            min_pv_for_commission: parseFloat(data.settings.min_pv_for_commission) || 100,
                            matching_bonus: parseFloat(data.settings.matching_bonus) || 3,
                        };

                        // Load unilevel percentages
                        if (data.settings.unilevel_percentages) {
                            try {
                                this.unilevelPercentages = JSON.parse(data.settings.unilevel_percentages);
                            } catch (e) {
                                this.updateUnilevelPercentages();
                            }
                        }

                        this.calculateTotal();
                    }
                }
            } catch (error) {
                console.error('Failed to load settings:', error);
            }
        },

        updateUnilevelPercentages() {
            const levels = this.settings.unilevel_levels;
            const newPercentages = [];
            const defaultPercentages = [5, 3, 2, 1, 1, 0.5, 0.5, 0.5, 0.5, 0.5];

            for (let i = 0; i < levels; i++) {
                newPercentages[i] = this.unilevelPercentages[i] || defaultPercentages[i] || 1;
            }
            this.unilevelPercentages = newPercentages;
        },

        calculateTotal() {
            // Calculate unilevel total
            this.unilevelTotal = this.settings.unilevel_enabled
                ? this.unilevelPercentages.reduce((sum, val) => sum + (parseFloat(val) || 0), 0)
                : 0;

            // Calculate total commission percentage
            this.totalCommissionPercentage = 0;

            if (this.settings.binary_enabled) {
                this.totalCommissionPercentage += parseFloat(this.settings.binary_match_commission) || 0;
            }

            if (this.settings.unilevel_enabled) {
                this.totalCommissionPercentage += this.unilevelTotal;
            }

            this.totalCommissionPercentage += parseFloat(this.settings.direct_referral_commission) || 0;
            this.totalCommissionPercentage += parseFloat(this.settings.matching_bonus) || 0;
        },

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('/admin/mlm/settings/update-placement', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        auto_placement_type: this.settings.auto_placement_type,
                        auto_placement: this.settings.auto_placement,
                        binary_enabled: this.settings.binary_enabled,
                        unilevel_enabled: this.settings.unilevel_enabled,
                        binary_match_commission: this.settings.binary_match_commission,
                        unilevel_levels: this.settings.unilevel_levels,
                        unilevel_percentages: JSON.stringify(this.unilevelPercentages),
                        direct_referral_commission: this.settings.direct_referral_commission,
                        min_pv_for_commission: this.settings.min_pv_for_commission,
                        matching_bonus: this.settings.matching_bonus,
                    }),
                });

                if (response.ok) {
                    this.showNotification('success', 'บันทึกการตั้งค่าเรียบร้อย');
                } else {
                    this.showNotification('error', 'ไม่สามารถบันทึกการตั้งค่าได้');
                }
            } catch (error) {
                console.error('Failed to save settings:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        },

        showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-300 ${
                type === 'success'
                    ? 'bg-green-500 text-white'
                    : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-xl"></i>
                    <span class="font-medium">${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateY(0)';
            }, 10);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    };
}
</script>

<style>
@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);
    cursor: pointer;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);
    cursor: pointer;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    border: none;
}

.delay-300 {
    animation-delay: 300ms;
}
</style>
@endsection
