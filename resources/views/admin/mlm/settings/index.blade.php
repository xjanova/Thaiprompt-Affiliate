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

    {{-- Overpay Warning Alert - ใช้ maxCommissionPercentage จาก settings (ไม่ hardcode) --}}
    <div x-show="totalCommissionPercentage > 0"
         x-transition
         class="rounded-2xl p-6 border-2 shadow-xl"
         :class="{
             'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700': totalCommissionPercentage > maxCommissionPercentage,
             'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700': totalCommissionPercentage > (maxCommissionPercentage * 0.8) && totalCommissionPercentage <= maxCommissionPercentage,
             'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700': totalCommissionPercentage <= (maxCommissionPercentage * 0.8)
         }">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                     :class="{
                         'bg-red-500': totalCommissionPercentage > maxCommissionPercentage,
                         'bg-yellow-500': totalCommissionPercentage > (maxCommissionPercentage * 0.8) && totalCommissionPercentage <= maxCommissionPercentage,
                         'bg-green-500': totalCommissionPercentage <= (maxCommissionPercentage * 0.8)
                     }">
                    <i class="fas text-white text-xl"
                       :class="{
                           'fa-exclamation-triangle': totalCommissionPercentage > maxCommissionPercentage,
                           'fa-exclamation-circle': totalCommissionPercentage > (maxCommissionPercentage * 0.8) && totalCommissionPercentage <= maxCommissionPercentage,
                           'fa-check-circle': totalCommissionPercentage <= (maxCommissionPercentage * 0.8)
                       }"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold mb-2"
                    :class="{
                        'text-red-800 dark:text-red-300': totalCommissionPercentage > maxCommissionPercentage,
                        'text-yellow-800 dark:text-yellow-300': totalCommissionPercentage > (maxCommissionPercentage * 0.8) && totalCommissionPercentage <= maxCommissionPercentage,
                        'text-green-800 dark:text-green-300': totalCommissionPercentage <= (maxCommissionPercentage * 0.8)
                    }">
                    <span x-text="`Effective Commission Rate: ${totalCommissionPercentage.toFixed(2)}% (ขีดจำกัด: ${maxCommissionPercentage}%)`"></span>
                </h3>
                <div class="space-y-2 text-sm"
                     :class="{
                         'text-red-700 dark:text-red-400': totalCommissionPercentage > maxCommissionPercentage,
                         'text-yellow-700 dark:text-yellow-400': totalCommissionPercentage > (maxCommissionPercentage * 0.8) && totalCommissionPercentage <= maxCommissionPercentage,
                         'text-green-700 dark:text-green-400': totalCommissionPercentage <= (maxCommissionPercentage * 0.8)
                     }">
                    <template x-if="totalCommissionPercentage > maxCommissionPercentage">
                        <div>
                            <strong class="flex items-center gap-2 mb-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span x-text="`Overpay! คอมมิชชั่นรวม ${totalCommissionPercentage.toFixed(2)}% เกินขีดจำกัด ${maxCommissionPercentage}%`"></span>
                            </strong>
                            <p>ระบบ Overpay Protection จะ scale down คอมมิชชั่นอัตโนมัติเมื่อจ่ายจริง แต่ควรแก้ไขให้อยู่ในเกณฑ์</p>
                            <div class="mt-3 p-3 bg-white/50 dark:bg-black/20 rounded-lg">
                                <p class="font-medium mb-1">คำแนะนำ:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>ลดเปอร์เซ็นต์ Binary / Unilevel</li>
                                    <li>ลดค่า Commission Per PV (ปัจจุบัน: <span x-text="settings.commission_per_pv"></span>)</li>
                                    <li>ลดจำนวนชั้น Unilevel / ลดเปอร์เซ็นต์แต่ละชั้น</li>
                                </ul>
                            </div>
                        </div>
                    </template>
                    <template x-if="totalCommissionPercentage > (maxCommissionPercentage * 0.8) && totalCommissionPercentage <= maxCommissionPercentage">
                        <div>
                            <strong class="flex items-center gap-2 mb-2">
                                <i class="fas fa-exclamation-circle"></i>
                                <span x-text="`คำเตือน - ใกล้ขีดจำกัด (เหลือ ${(maxCommissionPercentage - totalCommissionPercentage).toFixed(2)}%)`"></span>
                            </strong>
                            <p>เปอร์เซ็นต์คอมมิชชั่นอยู่ในระดับสูง กรุณาตรวจสอบ</p>
                        </div>
                    </template>
                    <template x-if="totalCommissionPercentage <= (maxCommissionPercentage * 0.8)">
                        <div>
                            <strong class="flex items-center gap-2 mb-2">
                                <i class="fas fa-check-circle"></i>
                                ปลอดภัย - เปอร์เซ็นต์เหมาะสม
                            </strong>
                            <p x-text="`ใช้ไป ${totalCommissionPercentage.toFixed(2)}% จากขีดจำกัด ${maxCommissionPercentage}% - เหลือพื้นที่อีก ${(maxCommissionPercentage - totalCommissionPercentage).toFixed(2)}%`"></p>
                        </div>
                    </template>
                </div>

                {{-- Breakdown - แสดง effective rate ที่ถูกต้อง --}}
                <div class="mt-4 p-4 bg-white/50 dark:bg-black/20 rounded-xl">
                    <p class="font-semibold mb-2">รายละเอียดการคำนวณ (Effective Rate = raw% x commission_per_pv):</p>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
                        <div :class="{'opacity-40': !settings.binary_enabled}">
                            <p class="opacity-75">Binary</p>
                            <p class="font-bold text-base" x-text="`${settings.binary_enabled ? ((settings.binary_match_commission || 0) * settings.commission_per_pv).toFixed(2) : 0}%`"></p>
                            <p class="opacity-50" x-show="settings.binary_enabled && settings.commission_per_pv != 1" x-text="`(raw: ${settings.binary_match_commission}% × ${settings.commission_per_pv})`"></p>
                        </div>
                        <div :class="{'opacity-40': !settings.unilevel_enabled}">
                            <p class="opacity-75">Unilevel</p>
                            <p class="font-bold text-base" x-text="`${settings.unilevel_enabled ? (unilevelTotal * settings.commission_per_pv).toFixed(2) : 0}%`"></p>
                            <p class="opacity-50" x-show="settings.unilevel_enabled && settings.commission_per_pv != 1" x-text="`(raw: ${unilevelTotal.toFixed(2)}% × ${settings.commission_per_pv})`"></p>
                        </div>
                        <div :class="{'opacity-40': !settings.genealogy_enabled}">
                            <p class="opacity-75">ค่าแนะนำตรง</p>
                            <p class="font-bold text-base" x-text="`${settings.genealogy_enabled ? (settings.direct_referral_commission || 0) : 0}%`"></p>
                            <p class="opacity-50" x-show="settings.genealogy_enabled">(%ของยอดสั่งซื้อ)</p>
                        </div>
                        <div>
                            <p class="opacity-75">Effective Rate รวม</p>
                            <p class="font-bold text-lg" :class="{'text-red-600': totalCommissionPercentage > maxCommissionPercentage}" x-text="`${totalCommissionPercentage.toFixed(2)}%`"></p>
                        </div>
                        <div>
                            <p class="opacity-75">ขีดจำกัด</p>
                            <p class="font-bold text-lg text-blue-600" x-text="`${maxCommissionPercentage}%`"></p>
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
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">ใช้สำหรับประมาณการเพดานจ่ายรวม (Overpay Protection)</p>
                    </div>

                    {{-- ค่าคอมต่อคู่ Binary (คีย์ที่ engine จ่ายจริง: binary_pair_commission) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-hand-holding-usd text-purple-500 mr-2"></i>
                            ค่าคอมมิชชั่นต่อคู่ (บาท/คู่)
                        </label>
                        <div class="relative">
                            <input type="number"
                                   x-model.number="settings.binary_pair_commission"
                                   min="0" step="1"
                                   :disabled="!settings.binary_enabled"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pr-20">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">บาท/คู่</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            เงินที่จ่ายจริงเมื่อ PV ขาซ้าย-ขวาจับคู่กันได้ 1 คู่ (ค่านี้คือค่าที่ระบบใช้จ่ายคอม Binary จริง)
                        </p>
                    </div>

                    {{-- Auto Placement Strategy --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                            กลยุทธ์การจัดเรียงอัตโนมัติ
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- ⚠️ ค่าต้องเป็น 'left_first' ให้ตรงกับ MlmBinaryService (เดิมเป็น 'left_to_right' ทำให้ save ล้มเหลว 422) --}}
                            <button type="button"
                                    @click="settings.auto_placement_type = 'left_first'"
                                    :disabled="!settings.auto_placement"
                                    class="p-4 rounded-xl border-2 transition-all text-left"
                                    :class="settings.auto_placement_type === 'left_first'
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-arrow-right text-2xl" :class="settings.auto_placement_type === 'left_first' ? 'text-purple-600' : 'text-gray-400'"></i>
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
                            จำนวนชั้น Unilevel (ความลึก)
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
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">กำหนดว่าจะจ่ายคอมมิชชั่นกี่ชั้นลึก</p>
                    </div>

                    {{-- Unilevel Max Width --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            ความกว้างสูงสุด (Max Width)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.unilevel_max_width"
                                   min="0" max="20" step="1"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                   :disabled="!settings.unilevel_enabled">
                            <div class="w-28 px-3 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-center font-bold">
                                <span x-text="settings.unilevel_max_width === 0 ? 'ไม่จำกัด' : settings.unilevel_max_width + ' คน'"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            จำนวนลูกทีมตรงสูงสุดต่อคน (0 = ไม่จำกัด) หากเกินจะ spillover ไปชั้นถัดไป
                        </p>
                    </div>

                    {{-- Spillover Info --}}
                    <div x-show="settings.unilevel_max_width > 0" x-transition class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                        <h4 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2 flex items-center gap-2">
                            <i class="fas fa-random"></i>
                            Spillover System
                        </h4>
                        <p class="text-xs text-green-700 dark:text-green-400">
                            เมื่อสมาชิกมีลูกทีมตรงครบ <strong x-text="settings.unilevel_max_width"></strong> คน สมาชิกใหม่ที่สมัครโดยรหัสของเขาจะถูก spillover ไปอยู่ใต้ลูกทีมที่มีช่องว่างอยู่
                        </p>
                    </div>

                    {{-- Rebuild Tree Section --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                            <i class="fas fa-sitemap"></i>
                            จัดเรียงผัง Unilevel ใหม่
                        </h4>

                        {{-- Rebuild Status --}}
                        <div x-show="rebuildTask && rebuildTask.status === 'running'" x-transition class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-blue-700 dark:text-blue-300">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>
                                    กำลังจัดเรียงผัง...
                                </span>
                                <span class="text-sm font-bold text-blue-800 dark:text-blue-200" x-text="`${rebuildTask.progress_percentage || 0}%`"></span>
                            </div>
                            <div class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-3">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-3 rounded-full transition-all duration-300"
                                     :style="`width: ${rebuildTask.progress_percentage || 0}%`"></div>
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-blue-600 dark:text-blue-400">
                                <span x-text="`${rebuildTask.processed_items || 0} / ${rebuildTask.total_items || 0} สมาชิก`"></span>
                                <span x-show="rebuildTask.failed_items > 0" class="text-red-500" x-text="`ล้มเหลว: ${rebuildTask.failed_items}`"></span>
                            </div>
                        </div>

                        {{-- Completed Status --}}
                        <div x-show="rebuildTask && rebuildTask.status === 'completed'" x-transition class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm font-medium">จัดเรียงผังเสร็จเรียบร้อย!</span>
                            </div>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1" x-text="`เสร็จเมื่อ: ${new Date(rebuildTask.completed_at).toLocaleString('th-TH')}`"></p>
                        </div>

                        {{-- Failed Status --}}
                        <div x-show="rebuildTask && rebuildTask.status === 'failed'" x-transition class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span class="text-sm font-medium">เกิดข้อผิดพลาด</span>
                            </div>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1" x-text="rebuildTask.error_message"></p>
                        </div>

                        {{-- Rebuild Button --}}
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click="showRebuildConfirm = true"
                                    :disabled="rebuildTask && rebuildTask.status === 'running'"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                                <i class="fas" :class="rebuildTask && rebuildTask.status === 'running' ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                                <span x-text="rebuildTask && rebuildTask.status === 'running' ? 'กำลังดำเนินการ...' : 'จัดเรียงผังใหม่ตาม Genealogy'"></span>
                            </button>
                            <button type="button"
                                    x-show="rebuildTask && rebuildTask.status === 'running'"
                                    @click="cancelRebuild()"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <i class="fas fa-stop"></i>
                                ยกเลิก
                            </button>
                        </div>

                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            การจัดเรียงใหม่จะใช้ผังสายเลือด (Genealogy) เป็นต้นแบบ และจัดวางใน Unilevel tree ตามความกว้างที่กำหนด
                        </p>
                    </div>

                    {{-- Rebuild Confirmation Modal --}}
                    <div x-show="showRebuildConfirm"
                         x-transition
                         @click.away="showRebuildConfirm = false"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                ยืนยันการจัดเรียงผังใหม่
                            </h3>
                            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                <p>การจัดเรียงผังใหม่จะ:</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>ย้ายสมาชิกไปตำแหน่งใหม่ตามลำดับการแนะนำจริง</li>
                                    <li>อัพเดท level และ path ของสมาชิกทุกคน</li>
                                    <li>ค่าคอมมิชชั่นอาจเปลี่ยนแปลงตาม level ใหม่</li>
                                </ul>
                                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                                    <p class="text-yellow-700 dark:text-yellow-300">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        กระบวนการจะทำงานต่อแม้คุณปิดหน้าเว็บ และสามารถกลับมาดู progress ได้
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-3 mt-6">
                                <button type="button"
                                        @click="showRebuildConfirm = false"
                                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                                    ยกเลิก
                                </button>
                                <button type="button"
                                        @click="startRebuild(); showRebuildConfirm = false"
                                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg flex items-center justify-center gap-2">
                                    <i class="fas fa-check"></i>
                                    เริ่มจัดเรียง
                                </button>
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

            {{-- PV System Settings --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-coins"></i>
                        PV System (Point Value)
                    </h2>
                    <p class="text-sm text-white/80 mt-1">ตั้งค่าอัตราแปลง PV เป็นเงินคอมมิชชั่น</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Commission per PV --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-exchange-alt text-orange-500 mr-2"></i>
                            อัตราแปลง PV เป็นเงิน (บาท/PV)
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1 relative">
                                <input type="number"
                                       x-model.number="settings.commission_per_pv"
                                       min="0" max="1000" step="0.01"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pr-16">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">บาท/PV</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            กำหนดว่า 1 PV จะแปลงเป็นเงินเท่าไหร่สำหรับคำนวณคอมมิชชั่น
                        </p>
                    </div>

                    {{-- Global PV Rate (คีย์จริงที่ engine ใช้แปลงยอดขาย → PV) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-percent text-orange-500 mr-2"></i>
                            อัตราแปลงยอดขายเป็น PV (PV/บาท)
                        </label>
                        <div class="relative">
                            <input type="number"
                                   x-model.number="settings.global_pv_rate"
                                   min="0.01" max="1000" step="0.01"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pr-20">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">PV/บาท</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            ใช้กับสินค้าที่ไม่ได้กำหนด PV เอง: PV = ยอดขาย × อัตรานี้ (เช่น 1 = ขาย 100 บาทได้ 100 PV)
                        </p>
                    </div>

                    {{-- Example Calculation --}}
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                        <h4 class="text-sm font-semibold text-orange-800 dark:text-orange-300 mb-3 flex items-center gap-2">
                            <i class="fas fa-calculator"></i>
                            ตัวอย่างการคำนวณ
                        </h4>
                        <div class="text-sm text-orange-700 dark:text-orange-400 space-y-2">
                            <p>
                                <span class="font-medium">สินค้ามี PV:</span> 100 PV
                            </p>
                            <p>
                                <span class="font-medium">อัตราแปลง:</span>
                                <span x-text="settings.commission_per_pv || 1"></span> บาท/PV
                            </p>
                            <p>
                                <span class="font-medium">มูลค่าคอมมิชชั่นฐาน:</span>
                                <span class="text-lg font-bold" x-text="(100 * (settings.commission_per_pv || 1)).toLocaleString()"></span> บาท
                            </p>
                            <div class="pt-2 border-t border-orange-200 dark:border-orange-700 mt-2">
                                <p class="text-xs opacity-80">
                                    * จากนั้นจะนำไปคูณกับ % คอมมิชชั่นของแต่ละระดับ (Unilevel/Binary)
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Info Box --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
                            <i class="fas fa-lightbulb"></i>
                            หมายเหตุสำคัญ
                        </h4>
                        <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                            <li>PV ของสินค้า/บริการ กำหนดโดยผู้ขายในหน้าจัดการสินค้า</li>
                            <li>สินค้าที่ไม่ได้กำหนด PV จะใช้อัตราแปลงกลางอัตโนมัติ (ยอดขาย × อัตราแปลงยอดขายเป็น PV)</li>
                            <li>คอมมิชชั่นจะถูกหักจากยอดขายก่อนโอนให้ร้านค้า</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Genealogy System (ผังสายเลือด - ค่าแนะนำตรง) --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-user-plus"></i>
                        ผังสายเลือด (Genealogy)
                    </h2>
                    <p class="text-sm text-white/80 mt-1">ค่าแนะนำตรง - จ่ายให้ผู้แนะนำจริง (ไม่ใช่ Unilevel/Binary)</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Genealogy Enabled Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งานค่าแนะนำตรง</label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">เปิด/ปิด ค่าคอมมิชชั่นจากการแนะนำตรง (ผังสายเลือด)</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.genealogy_enabled" @change="calculateTotal()" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-600 peer-checked:to-cyan-600"></div>
                        </label>
                    </div>

                    {{-- ประเภทค่าแนะนำตรง (คีย์จริง: direct_referral_bonus_type) --}}
                    <div x-show="settings.genealogy_enabled" x-transition>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                            รูปแบบค่าแนะนำตรง
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button"
                                    @click="settings.direct_referral_bonus_type = 'percentage'"
                                    class="p-3 rounded-xl border-2 transition-all text-center"
                                    :class="settings.direct_referral_bonus_type === 'percentage'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'">
                                <i class="fas fa-percent text-lg" :class="settings.direct_referral_bonus_type === 'percentage' ? 'text-blue-600' : 'text-gray-400'"></i>
                                <p class="font-semibold text-gray-900 dark:text-white text-sm mt-1">เปอร์เซ็นต์</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">% ของยอดสั่งซื้อ</p>
                            </button>
                            <button type="button"
                                    @click="settings.direct_referral_bonus_type = 'fixed'"
                                    class="p-3 rounded-xl border-2 transition-all text-center"
                                    :class="settings.direct_referral_bonus_type === 'fixed'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'">
                                <i class="fas fa-money-bill-wave text-lg" :class="settings.direct_referral_bonus_type === 'fixed' ? 'text-blue-600' : 'text-gray-400'"></i>
                                <p class="font-semibold text-gray-900 dark:text-white text-sm mt-1">จำนวนคงที่</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">บาท/ออเดอร์</p>
                            </button>
                        </div>
                    </div>

                    {{-- Direct Referral Commission (%) --}}
                    <div x-show="settings.genealogy_enabled && settings.direct_referral_bonus_type === 'percentage'" x-transition>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            ค่าแนะนำตรง (%)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.direct_referral_commission"
                                   @input="calculateTotal()"
                                   min="0" max="20" step="0.5"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                   :disabled="!settings.genealogy_enabled">
                            <div class="w-20 px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-center font-bold">
                                <span x-text="(settings.direct_referral_commission || 0) + '%'"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">คอมมิชชั่นจากการแนะนำโดยตรง (แนะนำ: 5-10%)</p>
                    </div>

                    {{-- Direct Referral Fixed Amount (คีย์จริง: direct_referral_bonus_amount) --}}
                    <div x-show="settings.genealogy_enabled && settings.direct_referral_bonus_type === 'fixed'" x-transition>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            ค่าแนะนำตรง (บาท/ออเดอร์)
                        </label>
                        <div class="relative">
                            <input type="number"
                                   x-model.number="settings.direct_referral_bonus_amount"
                                   min="0" step="1"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pr-14">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">บาท</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">จ่ายจำนวนคงที่ต่อออเดอร์ที่ชำระเงินแล้ว</p>
                    </div>

                    {{-- Info Box --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            ความแตกต่างกับ Unilevel/Binary
                        </h4>
                        <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                            <li><strong>ผังสายเลือด:</strong> ใครแนะนำใครจริงๆ (ใช้รหัสแนะนำของใคร)</li>
                            <li><strong>Unilevel:</strong> ตำแหน่งในสายงาน (อาจ spillover ไปคนอื่น)</li>
                            <li><strong>Binary:</strong> ตำแหน่งในโครงสร้าง 2 ขา (ตามกลยุทธ์ที่ตั้ง)</li>
                            <li>ค่าแนะนำตรงจ่ายให้ผู้แนะนำจริงเท่านั้น ไม่มี spillover</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Volume Retention System --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-heartbeat"></i>
                        ระบบรักษายอด (Volume Retention)
                    </h2>
                    <p class="text-sm text-white/80 mt-1">กำหนดเงื่อนไข PV รายเดือนที่สมาชิกต้องทำเพื่อรับคอมมิชชั่น</p>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Retention Enabled Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งานระบบรักษายอด</label>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">เมื่อเปิด สมาชิกต้องมียอด PV ตามเกณฑ์จึงจะรับคอมมิชชั่นได้</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.volume_retention_enabled" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:ring-4 peer-focus:ring-rose-300 dark:peer-focus:ring-rose-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-rose-600 peer-checked:to-pink-600"></div>
                        </label>
                    </div>

                    {{-- Monthly PV Requirement --}}
                    <div x-show="settings.volume_retention_enabled" x-transition>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-chart-bar text-rose-500 mr-2"></i>
                            PV ขั้นต่ำต่อเดือน
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1 relative">
                                <input type="number"
                                       x-model.number="settings.volume_retention_monthly_pv"
                                       min="0" max="10000" step="10"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pr-12">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">PV</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            สมาชิกต้องซื้อสินค้ารวม PV อย่างน้อยเท่านี้ภายในเดือนปัจจุบัน
                        </p>
                    </div>

                    {{-- Grace Period --}}
                    <div x-show="settings.volume_retention_enabled" x-transition>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-clock text-rose-500 mr-2"></i>
                            ระยะเวลาผ่อนผัน (Grace Period)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range"
                                   x-model.number="settings.volume_retention_grace_days"
                                   min="0" max="30" step="1"
                                   class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <div class="w-20 px-3 py-2 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-lg text-center font-bold">
                                <span x-text="settings.volume_retention_grace_days + ' วัน'"></span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            หลังจากซื้อครั้งสุดท้าย สมาชิกจะยังมีสิทธิ์รับคอมมิชชั่นอีกกี่วัน (0 = ไม่มีผ่อนผัน)
                        </p>
                    </div>

                    {{-- How It Works --}}
                    <div x-show="settings.volume_retention_enabled" x-transition class="p-4 bg-rose-50 dark:bg-rose-900/20 rounded-xl border border-rose-200 dark:border-rose-800">
                        <h4 class="text-sm font-semibold text-rose-800 dark:text-rose-300 mb-3 flex items-center gap-2">
                            <i class="fas fa-lightbulb"></i>
                            วิธีการทำงาน
                        </h4>
                        <ul class="text-xs text-rose-700 dark:text-rose-400 space-y-2 list-disc list-inside">
                            <li>ทุกเดือนระบบจะตรวจสอบ PV จากการซื้อส่วนตัวของสมาชิก</li>
                            <li>
                                ถ้า PV ≥ <strong x-text="settings.volume_retention_monthly_pv || 100"></strong> PV → <span class="text-green-700 dark:text-green-400 font-semibold">Active</span> (รับคอมมิชชั่นได้)
                            </li>
                            <li>
                                ถ้า PV ไม่ถึง แต่ซื้อล่าสุดไม่เกิน <strong x-text="settings.volume_retention_grace_days || 7"></strong> วัน → <span class="text-yellow-700 dark:text-yellow-400 font-semibold">Grace Period</span> (ยังรับได้ชั่วคราว)
                            </li>
                            <li>ถ้าเกินทั้ง 2 เงื่อนไข → <span class="text-red-700 dark:text-red-400 font-semibold">Inactive</span> (คอมมิชชั่นจะ Rollup ให้ Upline ที่ Active)</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Other Settings --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-gray-600 to-slate-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-cogs"></i>
                        การตั้งค่าอื่นๆ
                    </h2>
                    <p class="text-sm text-white/80 mt-1">การตั้งค่าเพิ่มเติม</p>
                </div>

                <div class="p-6 space-y-6">
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

                    {{-- Breakdown - 3 ระบบหลัก --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg"
                             :class="{'opacity-40': !settings.binary_enabled}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Binary</span>
                                <span x-show="!settings.binary_enabled" class="text-xs text-gray-400">(ปิด)</span>
                            </div>
                            <span class="font-bold text-purple-600" x-text="(settings.binary_enabled ? settings.binary_match_commission : 0) + '%'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg"
                             :class="{'opacity-40': !settings.unilevel_enabled}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Unilevel</span>
                                <span x-show="!settings.unilevel_enabled" class="text-xs text-gray-400">(ปิด)</span>
                            </div>
                            <span class="font-bold text-green-600" x-text="(settings.unilevel_enabled ? unilevelTotal : 0).toFixed(2) + '%'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
                             :class="{'opacity-40': !settings.genealogy_enabled}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">ผังสายเลือด</span>
                                <span x-show="!settings.genealogy_enabled" class="text-xs text-gray-400">(ปิด)</span>
                            </div>
                            <span class="font-bold text-blue-600" x-text="(settings.genealogy_enabled ? (settings.direct_referral_commission || 0) : 0) + '%'"></span>
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
            binary_pair_commission: 100,
            auto_placement_type: 'balanced',
            auto_placement: true,
            unilevel_enabled: true,
            unilevel_levels: 5,
            unilevel_max_width: 0,
            genealogy_enabled: true,
            direct_referral_commission: 5,
            direct_referral_bonus_type: 'fixed',
            direct_referral_bonus_amount: 100,
            min_pv_for_commission: 100,
            commission_per_pv: 1,
            global_pv_rate: 1,
            volume_retention_enabled: true,
            volume_retention_monthly_pv: 100,
            volume_retention_grace_days: 7,
        },
        unilevelPercentages: [5, 3, 2, 1, 1],
        saving: false,
        totalCommissionPercentage: 0,
        pvEffectiveRate: 0,
        unilevelTotal: 0,
        maxCommissionPercentage: {{ \App\Models\MlmGlobalSetting::get('max_commission_percentage', 40) }},

        // Rebuild task tracking
        rebuildTask: null,
        showRebuildConfirm: false,
        rebuildPolling: null,

        init() {
            this.loadSettings();
            this.calculateTotal();
            this.checkRebuildStatus();
        },

        async loadSettings() {
            try {
                const response = await fetch('/admin/mlm/settings/get');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.settings) {
                        // ⚠️ ค่าจริงเก็บที่ auto_placement_strategy (auto_placement_type คือคีย์เก่า)
                        //    และ map ค่า legacy 'left_to_right' → 'left_first'
                        let strategy = data.settings.auto_placement_strategy
                            || data.settings.auto_placement_type
                            || 'balanced';
                        if (strategy === 'left_to_right') strategy = 'left_first';

                        // unilevel_levels ใน DB เป็น array [{level, percentage}] — นับจำนวนชั้นจาก array
                        let levelCount = 5;
                        if (Array.isArray(data.settings.unilevel_levels)) {
                            levelCount = data.settings.unilevel_levels.length || 5;
                        } else if (parseInt(data.settings.unilevel_levels)) {
                            levelCount = parseInt(data.settings.unilevel_levels);
                        }

                        this.settings = {
                            binary_enabled: data.settings.binary_enabled ?? true,
                            binary_match_commission: parseFloat(data.settings.binary_match_percentage ?? data.settings.binary_match_commission) || 10,
                            binary_pair_commission: parseFloat(data.settings.binary_pair_commission) || 100,
                            auto_placement_type: strategy,
                            auto_placement: (data.settings.auto_placement_enabled ?? data.settings.auto_placement) ?? true,
                            unilevel_enabled: data.settings.unilevel_enabled ?? true,
                            unilevel_levels: levelCount,
                            unilevel_max_width: parseInt(data.settings.unilevel_max_width) || 0,
                            genealogy_enabled: data.settings.genealogy_enabled ?? true,
                            direct_referral_commission: parseFloat(data.settings.direct_referral_bonus_percentage ?? data.settings.direct_referral_commission) || 5,
                            direct_referral_bonus_type: data.settings.direct_referral_bonus_type || 'fixed',
                            direct_referral_bonus_amount: parseFloat(data.settings.direct_referral_bonus_amount) || 100,
                            min_pv_for_commission: parseFloat(data.settings.min_pv_for_commission) || 100,
                            commission_per_pv: parseFloat(data.settings.commission_per_pv) || 1,
                            global_pv_rate: parseFloat(data.settings.global_pv_rate) || 1,
                            volume_retention_enabled: data.settings.volume_retention_enabled ?? true,
                            volume_retention_monthly_pv: parseFloat(data.settings.volume_retention_monthly_pv) || 100,
                            volume_retention_grace_days: parseInt(data.settings.volume_retention_grace_days) || 7,
                        };

                        // โหลดเปอร์เซ็นต์แต่ละชั้น — ใช้จาก unilevel_levels array ก่อน (source of truth ของ engine)
                        if (Array.isArray(data.settings.unilevel_levels) && data.settings.unilevel_levels.length) {
                            this.unilevelPercentages = data.settings.unilevel_levels.map(l => parseFloat(l.percentage) || 0);
                        } else if (data.settings.unilevel_percentages) {
                            try {
                                const parsed = typeof data.settings.unilevel_percentages === 'string'
                                    ? JSON.parse(data.settings.unilevel_percentages)
                                    : data.settings.unilevel_percentages;
                                if (Array.isArray(parsed)) this.unilevelPercentages = parsed;
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
            const cpv = parseFloat(this.settings.commission_per_pv) || 1;

            // คำนวณ unilevel total (raw %)
            this.unilevelTotal = this.settings.unilevel_enabled
                ? this.unilevelPercentages.reduce((sum, val) => sum + (parseFloat(val) || 0), 0)
                : 0;

            // คำนวณ Effective Commission Rate
            // สูตร: (Binary% + Unilevel%) × commissionPerPv + DirectReferral%
            // ⚠️ Binary + Unilevel คำนวณจาก PV → ต้องคูณ commissionPerPv
            // ⚠️ Direct Referral คำนวณจาก % ของยอดสั่งซื้อ → ไม่ต้องคูณ commissionPerPv
            let pvBasedTotal = 0;
            let orderBasedTotal = 0;

            if (this.settings.binary_enabled) {
                pvBasedTotal += parseFloat(this.settings.binary_match_commission) || 0;
            }

            if (this.settings.unilevel_enabled) {
                pvBasedTotal += this.unilevelTotal;
            }

            if (this.settings.genealogy_enabled) {
                orderBasedTotal += parseFloat(this.settings.direct_referral_commission) || 0;
            }

            // Effective rate = PV-based × commissionPerPv + order-based
            this.pvEffectiveRate = pvBasedTotal * cpv;
            this.totalCommissionPercentage = this.pvEffectiveRate + orderBasedTotal;
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
                        genealogy_enabled: this.settings.genealogy_enabled,
                        binary_match_commission: this.settings.binary_match_commission,
                        binary_pair_commission: this.settings.binary_pair_commission,
                        global_pv_rate: this.settings.global_pv_rate,
                        unilevel_levels: this.settings.unilevel_levels,
                        unilevel_max_width: this.settings.unilevel_max_width,
                        unilevel_percentages: JSON.stringify(this.unilevelPercentages),
                        direct_referral_commission: this.settings.direct_referral_commission,
                        direct_referral_bonus_type: this.settings.direct_referral_bonus_type,
                        direct_referral_bonus_amount: this.settings.direct_referral_bonus_amount,
                        min_pv_for_commission: this.settings.min_pv_for_commission,
                        commission_per_pv: this.settings.commission_per_pv,
                        volume_retention_enabled: this.settings.volume_retention_enabled,
                        volume_retention_monthly_pv: this.settings.volume_retention_monthly_pv,
                        volume_retention_grace_days: this.settings.volume_retention_grace_days,
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

        // =========================================
        // Rebuild Task Methods
        // =========================================

        async checkRebuildStatus() {
            try {
                const response = await fetch('/admin/mlm/settings/rebuild-status');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.task) {
                        this.rebuildTask = data.task;

                        // ถ้ากำลัง running ให้ poll ต่อ
                        if (this.rebuildTask.status === 'running') {
                            this.startPolling();
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to check rebuild status:', error);
            }
        },

        startPolling() {
            // หยุด polling เดิมถ้ามี
            if (this.rebuildPolling) {
                clearInterval(this.rebuildPolling);
            }

            // Poll ทุก 2 วินาที
            this.rebuildPolling = setInterval(async () => {
                try {
                    const response = await fetch('/admin/mlm/settings/rebuild-status');
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.task) {
                            this.rebuildTask = data.task;

                            // หยุด polling ถ้าเสร็จหรือ failed
                            if (this.rebuildTask.status !== 'running') {
                                clearInterval(this.rebuildPolling);
                                this.rebuildPolling = null;

                                if (this.rebuildTask.status === 'completed') {
                                    this.showNotification('success', 'จัดเรียงผังเสร็จเรียบร้อย!');
                                } else if (this.rebuildTask.status === 'failed') {
                                    this.showNotification('error', 'เกิดข้อผิดพลาดในการจัดเรียงผัง');
                                }
                            }
                        }
                    }
                } catch (error) {
                    console.error('Failed to poll rebuild status:', error);
                }
            }, 2000);
        },

        async startRebuild() {
            try {
                const response = await fetch('/admin/mlm/settings/start-rebuild', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.rebuildTask = data.task;
                    this.showNotification('success', data.message);
                    this.startPolling();
                } else {
                    this.showNotification('error', data.error || 'ไม่สามารถเริ่มการจัดเรียงได้');
                }
            } catch (error) {
                console.error('Failed to start rebuild:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการเริ่มจัดเรียง');
            }
        },

        async cancelRebuild() {
            if (!this.rebuildTask) return;

            try {
                const response = await fetch('/admin/mlm/settings/cancel-rebuild', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        task_id: this.rebuildTask.id,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.rebuildTask = data.task;
                    this.showNotification('success', 'ยกเลิกการจัดเรียงเรียบร้อย');

                    // หยุด polling
                    if (this.rebuildPolling) {
                        clearInterval(this.rebuildPolling);
                        this.rebuildPolling = null;
                    }
                } else {
                    this.showNotification('error', data.error || 'ไม่สามารถยกเลิกได้');
                }
            } catch (error) {
                console.error('Failed to cancel rebuild:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการยกเลิก');
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
