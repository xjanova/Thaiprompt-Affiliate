@extends('layouts.user-arrow-x')

@section('title', 'จำลองรายได้ MLM - เครื่องมือวางแผนรายได้')

@section('content')
<div class="min-h-screen py-8" x-data="incomeSimulator()" x-init="init()">
    <div class="container-fluid px-4">

        {{-- Hero Section with Gradient --}}
        <div class="mb-8 text-center animate-fade-in">
            <h1 class="text-5xl md:text-6xl font-black mb-4
                       bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                       dark:from-purple-400 dark:via-pink-400 dark:to-orange-400
                       bg-clip-text text-transparent">
                💰 จำลองรายได้ MLM
            </h1>
            <p class="text-xl md:text-2xl text-gray-700 dark:text-gray-300 mb-2 font-semibold">
                วางแผนและคาดการณ์รายได้ของคุณอย่างแม่นยำ
            </p>
            <p class="text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                🎯 เครื่องมือวิเคราะห์รายได้แบบมืออาชีพ ใช้ค่าจริงจากระบบ MLM • ครอบคลุมทุกประเภทค่าคอมมิชชั่น • พร้อมคำแนะนำเชิงกลยุทธ์
            </p>
        </div>

        {{-- Settings Info Panel - Always Visible --}}
        <div class="glass-fusion-card rounded-2xl shadow-2xl p-6 mb-6 backdrop-blur-xl
                    bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                    animate-slide-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600
                                 flex items-center justify-center text-white shadow-lg">
                        ⚙️
                    </span>
                    การตั้งค่าระบบ MLM
                </h3>
                <button @click="showSettingsHelp = !showSettingsHelp"
                        class="px-4 py-2 rounded-lg bg-blue-100 dark:bg-blue-900/30
                               text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50
                               transition-all text-sm font-semibold">
                    <i class="fas fa-question-circle mr-1"></i>
                    <span x-text="showSettingsHelp ? 'ซ่อนคำอธิบาย' : 'ดูคำอธิบาย'"></span>
                </button>
            </div>

            {{-- Settings Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" x-show="settings">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20
                            p-4 rounded-xl border-2 border-blue-200 dark:border-blue-700/50">
                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">🔢 PV Rate</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="settings.global_pv_rate || '1.00'"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">PV ต่อ 1 บาท</div>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20
                            p-4 rounded-xl border-2 border-purple-200 dark:border-purple-700/50">
                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">📊 Unilevel Depth</div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="settings.unilevel_max_depth || '10'"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ชั้นสูงสุด</div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20
                            p-4 rounded-xl border-2 border-green-200 dark:border-green-700/50">
                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">💰 Binary Commission</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        ฿<span x-text="settings.binary_pair_commission || '0'"></span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ต่อ 1 คู่</div>
                </div>

                <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20
                            p-4 rounded-xl border-2 border-orange-200 dark:border-orange-700/50">
                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">🔄 Pairing Type</div>
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400" x-text="settings.binary_pairing_type || '1:1'"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">แบบจับคู่</div>
                </div>
            </div>

            {{-- Help Text --}}
            <div x-show="showSettingsHelp"
                 x-transition
                 class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-700/50">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                    <strong>💡 คำอธิบาย:</strong> ค่าเหล่านี้มาจากการตั้งค่าจริงของระบบ MLM
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                    <li><strong>PV Rate:</strong> อัตราแปลงยอดขายเป็น PV (Personal Volume)</li>
                    <li><strong>Unilevel Depth:</strong> ระบบจะคำนวณค่าคอมมิชชั่นลึกสูงสุดกี่ชั้น</li>
                    <li><strong>Binary Commission:</strong> ค่าคอมมิชชั่นที่ได้ต่อการจับคู่ 1 คู่</li>
                    <li><strong>Pairing Type:</strong> สัดส่วนการจับคู่ระหว่างขาซ้าย-ขวา (1:1 หรือ 2:1)</li>
                </ul>
            </div>
        </div>

        {{-- Input Panel --}}
        <div class="glass-fusion-card rounded-2xl shadow-2xl p-6 mb-6 backdrop-blur-xl
                    bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                    animate-slide-up" style="animation-delay: 0.1s;">

            {{-- Rank Selection --}}
            <div class="mb-6 p-5 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20
                        rounded-xl border-2 border-yellow-300 dark:border-yellow-700/50">
                <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-500 to-orange-600
                                 flex items-center justify-center text-white text-lg shadow-lg">
                        ⭐
                    </span>
                    ระดับยศของคุณ (Rank)
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-auto">
                        • ไม่บังคับ - ถ้าไม่เลือกจะคำนวณแบบพื้นฐาน
                    </span>
                </label>

                <select x-model="selectedRankId"
                        @change="updateRankInfo()"
                        class="w-full px-4 py-3 text-lg font-semibold
                               border-2 border-yellow-400 dark:border-yellow-600 rounded-xl
                               focus:ring-4 focus:ring-yellow-200 dark:focus:ring-yellow-700/50
                               focus:border-yellow-500 dark:focus:border-yellow-500
                               bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                               transition-all">
                    <option value="">ไม่เลือกยศ (คำนวณแบบพื้นฐาน)</option>
                    <template x-for="rank in ranks" :key="rank.id">
                        <option :value="rank.id"
                                x-text="`${rank.name_th || rank.name} - ${rank.commission_rate}% + ${rank.bonus_multiplier}x โบนัส`">
                        </option>
                    </template>
                </select>

                {{-- Rank Info Display --}}
                <div x-show="selectedRank" x-transition class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">ค่าคอมมิชชั่นพื้นฐาน</div>
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                <span x-text="selectedRank ? selectedRank.commission_rate : 0"></span>%
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">จาก PV ส่วนตัว</div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">ตัวคูณโบนัส</div>
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                <span x-text="selectedRank ? selectedRank.bonus_multiplier.toFixed(2) : 1"></span>x
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">คูณ Unilevel + Binary</div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-semibold">ระดับยศ</div>
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                <span x-text="selectedRank ? selectedRank.level : 1"></span> / 5
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Level ปัจจุบัน</div>
                        </div>
                    </div>
                </div>

                {{-- No Rank Selected Info --}}
                <div x-show="!selectedRank" x-transition class="mt-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-200 dark:border-blue-700/50">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center text-white flex-shrink-0">
                                💡
                            </div>
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>ไม่ได้เลือกยศ:</strong> จะคำนวณรายได้แบบพื้นฐาน
                                <span class="block mt-1 text-blue-600 dark:text-blue-300">
                                    • ไม่มีค่าคอมมิชชั่นส่วนตัว (Personal Commission)
                                    <br>• Unilevel และ Binary จะไม่มีตัวคูณโบนัสจากยศ (multiplier = 1.0x)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Personal Sales --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600
                                     flex items-center justify-center text-white text-xs">
                            💵
                        </span>
                        ยอดขายของคุณ
                        <button @click="showTooltip('personal')" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </label>
                    <input type="number"
                           x-model.number="inputs.personalSales"
                           step="1000"
                           min="0"
                           class="w-full px-4 py-3 text-lg font-semibold
                                  border-2 border-purple-300 dark:border-purple-600 rounded-xl
                                  focus:ring-4 focus:ring-purple-200 dark:focus:ring-purple-700/50
                                  focus:border-purple-500 dark:focus:border-purple-400
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                  transition-all">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">บาท/เดือน</div>
                </div>

                {{-- Team Size --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-pink-500 to-red-600
                                     flex items-center justify-center text-white text-xs">
                            👥
                        </span>
                        จำนวนสมาชิกในทีม
                        <button @click="showTooltip('team')" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </label>
                    <input type="number"
                           x-model.number="inputs.teamSize"
                           step="1"
                           min="0"
                           class="w-full px-4 py-3 text-lg font-semibold
                                  border-2 border-pink-300 dark:border-pink-600 rounded-xl
                                  focus:ring-4 focus:ring-pink-200 dark:focus:ring-pink-700/50
                                  focus:border-pink-500 dark:focus:border-pink-400
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                  transition-all">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">คน</div>
                </div>

                {{-- Team Avg Sales --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600
                                     flex items-center justify-center text-white text-xs">
                            🛒
                        </span>
                        ยอดขายเฉลี่ยต่อคน
                        <button @click="showTooltip('avg')" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </label>
                    <input type="number"
                           x-model.number="inputs.teamAvgSales"
                           step="500"
                           min="0"
                           class="w-full px-4 py-3 text-lg font-semibold
                                  border-2 border-blue-300 dark:border-blue-600 rounded-xl
                                  focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-700/50
                                  focus:border-blue-500 dark:focus:border-blue-400
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                  transition-all">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">บาท/คน/เดือน</div>
                </div>

                {{-- Months --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600
                                     flex items-center justify-center text-white text-xs">
                            📅
                        </span>
                        ระยะเวลา
                        <button @click="showTooltip('months')" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </label>
                    <input type="number"
                           x-model.number="inputs.months"
                           step="1"
                           min="1"
                           max="60"
                           class="w-full px-4 py-3 text-lg font-semibold
                                  border-2 border-green-300 dark:border-green-600 rounded-xl
                                  focus:ring-4 focus:ring-green-200 dark:focus:ring-green-700/50
                                  focus:border-green-500 dark:focus:border-green-400
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                  transition-all">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">เดือน (1-60)</div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-6 flex flex-col sm:flex-row gap-4">
                <button @click="startSimulation()"
                        :disabled="isRunning"
                        class="flex-1 group relative overflow-hidden px-8 py-4
                               bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                               hover:from-purple-700 hover:via-pink-700 hover:to-orange-700
                               disabled:from-gray-400 disabled:via-gray-500 disabled:to-gray-600
                               text-white text-lg font-bold rounded-xl shadow-lg
                               hover:shadow-2xl hover:scale-[1.02]
                               disabled:hover:scale-100 disabled:cursor-not-allowed
                               transition-all duration-300">
                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent
                                -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                    <span class="relative z-10 flex items-center justify-center gap-2">
                        <i class="fas" :class="isRunning ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                        <span x-text="isRunning ? 'กำลังจำลอง...' : '▶️ เริ่มจำลอง'"></span>
                    </span>
                </button>

                <button @click="resetSimulation()"
                        class="px-8 py-4 bg-gray-600 hover:bg-gray-700
                               text-white text-lg font-bold rounded-xl shadow-lg
                               hover:shadow-xl transition-all duration-300">
                    🔄 รีเซ็ต
                </button>

                <button @click="printResults()"
                        x-show="showResults"
                        class="px-8 py-4 bg-green-600 hover:bg-green-700
                               text-white text-lg font-bold rounded-xl shadow-lg
                               hover:shadow-xl transition-all duration-300">
                    🖨️ พิมพ์
                </button>
            </div>

            {{-- Quick Tips --}}
            <div class="mt-6 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20
                        rounded-xl border border-indigo-200 dark:border-indigo-700/50">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center text-white flex-shrink-0">
                        💡
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <strong class="text-indigo-600 dark:text-indigo-400">เคล็ดลับ:</strong>
                        ลองปรับค่าต่างๆ เพื่อดูว่าแต่ละปัจจัยส่งผลต่อรายได้อย่างไร •
                        เลือกยศให้ตรงกับเป้าหมาย •
                        ยอดขายทีมมีผลมากต่อ Binary และ Unilevel
                    </div>
                </div>
            </div>
        </div>

        {{-- Animation Container --}}
        <div x-show="showAnimation"
             x-transition
             class="space-y-6 mb-6">

            {{-- Current Month Display --}}
            <div class="relative overflow-hidden rounded-2xl shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

                <div class="relative z-10 text-white p-8 text-center">
                    <h2 class="text-2xl font-bold mb-2">กำลังจำลอง...</h2>
                    <div class="text-7xl font-bold mb-2 animate-pulse">
                        เดือนที่ <span x-text="currentMonth"></span>
                    </div>
                    <div class="text-xl">จาก <span x-text="inputs.months"></span> เดือน</div>
                </div>
            </div>

            {{-- Real-time Income Display --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Personal Commission --}}
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75"></div>

                        <div class="relative bg-gradient-to-br from-green-400 to-green-600 rounded-2xl shadow-2xl p-6 text-white">
                            <div class="text-sm font-semibold mb-2 opacity-90 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    📦
                                </span>
                                คอมมิชชั่นส่วนตัว
                            </div>
                            <div class="text-4xl font-bold mb-2">
                                ฿<span x-text="formatNumber(currentIncome.personal)"></span>
                            </div>
                            <div class="text-sm opacity-75" x-text="selectedRank ? `จาก PV × ${selectedRank.commission_rate}%` : 'ต้องมียศ'"></div>
                        </div>
                    </div>
                </div>

                {{-- Team Commission --}}
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75"></div>

                        <div class="relative bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl shadow-2xl p-6 text-white">
                            <div class="text-sm font-semibold mb-2 opacity-90 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    👥
                                </span>
                                คอมมิชชั่นจากทีม
                            </div>
                            <div class="text-4xl font-bold mb-2">
                                ฿<span x-text="formatNumber(currentIncome.team)"></span>
                            </div>
                            <div class="text-sm opacity-75">Unilevel + Binary</div>
                        </div>
                    </div>
                </div>

                {{-- Total Income --}}
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 animate-pulse"></div>

                        <div class="relative bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-2xl p-6 text-white animate-pulse">
                            <div class="text-sm font-semibold mb-2 opacity-90 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    💰
                                </span>
                                รวมคอมมิชชั่น
                            </div>
                            <div class="text-4xl font-bold mb-2">
                                ฿<span x-text="formatNumber(currentIncome.total)"></span>
                            </div>
                            <div class="text-sm opacity-75">รวมทั้งหมดเดือนนี้</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="glass-fusion-card rounded-2xl shadow-2xl p-6 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
                <div class="flex justify-between text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <span>ความคืบหน้า</span>
                    <span x-text="`${progress}%`"></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-500"
                         :style="`width: ${progress}%`"></div>
                </div>
            </div>
        </div>

        {{-- Results Container --}}
        <div x-show="showResults"
             x-transition
             class="space-y-6">

            {{-- Strategic Insights --}}
            <div class="glass-fusion-card rounded-2xl shadow-2xl p-6 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600
                                 flex items-center justify-center text-white shadow-lg">
                        🎯
                    </span>
                    คำแนะนำเชิงกลยุทธ์
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="insights.length > 0">
                    <template x-for="(insight, index) in insights" :key="index">
                        <div class="p-4 rounded-xl border-l-4"
                             :class="{
                                 'bg-green-50 dark:bg-green-900/20 border-green-500': insight.type === 'success',
                                 'bg-blue-50 dark:bg-blue-900/20 border-blue-500': insight.type === 'info',
                                 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500': insight.type === 'warning',
                                 'bg-purple-50 dark:bg-purple-900/20 border-purple-500': insight.type === 'tip'
                             }">
                            <div class="flex items-start gap-3">
                                <div class="text-2xl flex-shrink-0" x-text="insight.icon"></div>
                                <div class="flex-1">
                                    <div class="font-bold text-gray-800 dark:text-white mb-1" x-text="insight.title"></div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300" x-text="insight.message"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Key Metrics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl blur-xl opacity-50"></div>

                        <div class="relative bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                            <div class="text-5xl mb-3">💎</div>
                            <div class="text-3xl font-bold mb-2">
                                ฿<span x-text="formatNumber(metrics.avgMonthly)"></span>
                            </div>
                            <div class="text-sm font-semibold">รายได้เฉลี่ย/เดือน</div>
                        </div>
                    </div>
                </div>

                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-teal-500 rounded-2xl blur-xl opacity-50"></div>

                        <div class="relative bg-gradient-to-br from-green-400 to-teal-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                            <div class="text-5xl mb-3">🎯</div>
                            <div class="text-3xl font-bold mb-2">
                                ฿<span x-text="formatNumber(metrics.bestMonth)"></span>
                            </div>
                            <div class="text-sm font-semibold">เดือนที่ได้สูงสุด</div>
                        </div>
                    </div>
                </div>

                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl blur-xl opacity-50"></div>

                        <div class="relative bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                            <div class="text-5xl mb-3">⚡</div>
                            <div class="text-3xl font-bold mb-2">
                                <span x-text="formatNumber(metrics.growthRate)"></span>%
                            </div>
                            <div class="text-sm font-semibold">อัตราการเติบโต</div>
                        </div>
                    </div>
                </div>

                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl blur-xl opacity-50"></div>

                        <div class="relative bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                            <div class="text-5xl mb-3">🚀</div>
                            <div class="text-3xl font-bold mb-2">
                                ฿<span x-text="formatNumber(metrics.yearlyProjection)"></span>
                            </div>
                            <div class="text-sm font-semibold">คาดการณ์รายได้/ปี</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Monthly Breakdown Table --}}
            <div class="glass-fusion-card rounded-2xl shadow-2xl p-8 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
                <h3 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
                    📊 สรุปรายได้แต่ละเดือน
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                            <tr>
                                <th class="px-4 py-4 text-left font-bold rounded-tl-xl">เดือน</th>
                                <th class="px-4 py-4 text-right font-bold">ยอดขายตัวเอง</th>
                                <th class="px-4 py-4 text-right font-bold">ยอดขายทีม</th>
                                <th class="px-4 py-4 text-right font-bold">Com.ส่วนตัว</th>
                                <th class="px-4 py-4 text-right font-bold">Unilevel</th>
                                <th class="px-4 py-4 text-right font-bold">Binary</th>
                                <th class="px-4 py-4 text-right font-bold">รวม</th>
                                <th class="px-4 py-4 text-right font-bold rounded-tr-xl">สะสม</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(month, index) in monthlyData" :key="index">
                                <tr class="hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                        เดือน <span x-text="month.month"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                        ฿<span x-text="formatNumber(month.personalSales)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                        ฿<span x-text="formatNumber(month.teamSales)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-semibold">
                                        ฿<span x-text="formatNumber(month.personal)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-semibold">
                                        ฿<span x-text="formatNumber(month.unilevel)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-indigo-600 dark:text-indigo-400 font-semibold">
                                        ฿<span x-text="formatNumber(month.binary)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-purple-600 dark:text-purple-400 font-bold text-lg">
                                        ฿<span x-text="formatNumber(month.total)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                        ฿<span x-text="formatNumber(month.accumulated)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gradient-to-r from-green-100 to-blue-100 dark:from-green-900/30 dark:to-blue-900/30 font-bold text-lg">
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-right text-gray-800 dark:text-gray-200">รวมทั้งหมด:</td>
                                <td class="px-4 py-4 text-right text-purple-600 dark:text-purple-400">
                                    ฿<span x-text="formatNumber(metrics.grandTotal)"></span>
                                </td>
                                <td class="px-4 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="glass-fusion-card rounded-2xl shadow-2xl p-6 backdrop-blur-xl
                            bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">📈 กราฟการเติบโตของรายได้</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="income-chart"></canvas>
                    </div>
                </div>

                <div class="glass-fusion-card rounded-2xl shadow-2xl p-6 backdrop-blur-xl
                            bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">🥧 สัดส่วนรายได้</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="breakdown-chart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Call to Action --}}
            <div class="relative overflow-hidden rounded-2xl shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

                <div class="relative z-10 p-8 text-white text-center">
                    <h3 class="text-3xl font-bold mb-4">🎉 พร้อมเริ่มต้นหรือยัง?</h3>
                    <p class="text-xl mb-6 opacity-90">นี่คือโอกาสของคุณที่จะสร้างรายได้แบบ Passive Income</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('user.mlm.dashboard') }}"
                           class="px-8 py-4 bg-white dark:bg-gray-800 text-purple-600 dark:text-purple-400
                                  rounded-xl font-bold text-lg hover:bg-gray-100 dark:hover:bg-gray-700
                                  shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300 inline-block">
                            เริ่มต้นเลย!
                        </a>
                        <button @click="startSimulation()"
                                class="px-8 py-4 bg-purple-800 hover:bg-purple-900 text-white
                                       rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl hover:scale-105
                                       transition-all duration-300">
                            จำลองอีกครั้ง
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tooltip Modal --}}
        <div x-show="tooltip.show"
             x-transition
             @click.outside="tooltip.show = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
                <button @click="tooltip.show = false"
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600
                                flex items-center justify-center text-white text-2xl">
                        💡
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white" x-text="tooltip.title"></h3>
                </div>

                <div class="text-gray-700 dark:text-gray-300" x-html="tooltip.content"></div>
            </div>
        </div>
    </div>
</div>

<style>
.perspective-1000 {
    perspective: 1000px;
}

.rotate-y-2 {
    transform: rotateY(2deg);
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

@push('scripts')
<script>
/**
 * Income Simulator Component - จำลองรายได้ MLM
 * ใช้ Alpine.js และ V3 Design System
 *
 * @returns {object} Alpine component
 */
function incomeSimulator() {
    return {
        // ===== State =====
        settings: null,
        ranks: [],
        selectedRankId: '',
        selectedRank: null,

        inputs: {
            personalSales: 10000,
            teamSize: 10,
            teamAvgSales: 5000,
            months: 12,
        },

        isRunning: false,
        showAnimation: false,
        showResults: false,
        showSettingsHelp: false,

        currentMonth: 0,
        progress: 0,

        currentIncome: {
            personal: 0,
            team: 0,
            total: 0,
        },

        monthlyData: [],

        metrics: {
            avgMonthly: 0,
            bestMonth: 0,
            growthRate: 0,
            yearlyProjection: 0,
            grandTotal: 0,
        },

        insights: [],

        tooltip: {
            show: false,
            title: '',
            content: '',
        },

        incomeChart: null,
        breakdownChart: null,

        // ===== Lifecycle =====

        /**
         * เริ่มต้น component
         */
        async init() {
            await this.loadSettings();
            await this.loadRanks();
        },

        // ===== Methods =====

        /**
         * โหลดการตั้งค่า MLM
         */
        async loadSettings() {
            try {
                const response = await fetch('{{ route("admin.mlm.settings.get-settings") }}');
                const data = await response.json();
                this.settings = data.settings;
            } catch (error) {
                console.error('Failed to load settings:', error);
                this.settings = this.getDefaultSettings();
            }
        },

        /**
         * โหลด Ranks
         */
        async loadRanks() {
            try {
                const response = await fetch('/api/v1/ranks');
                const data = await response.json();
                this.ranks = data.data || data;
            } catch (error) {
                console.error('Failed to load ranks:', error);
                this.ranks = [];
            }
        },

        /**
         * อัพเดทข้อมูล Rank ที่เลือก
         */
        updateRankInfo() {
            if (!this.selectedRankId) {
                this.selectedRank = null;
                return;
            }

            this.selectedRank = this.ranks.find(r => r.id == this.selectedRankId);
        },

        /**
         * เริ่มการจำลอง
         */
        async startSimulation() {
            if (this.isRunning) return;

            this.isRunning = true;
            this.showAnimation = true;
            this.showResults = false;
            this.monthlyData = [];
            this.currentMonth = 0;
            this.progress = 0;

            // รัน simulation แต่ละเดือน
            for (let month = 1; month <= this.inputs.months; month++) {
                this.currentMonth = month;
                await this.simulateMonth(month);
                this.updateProgress(month, this.inputs.months);
                await this.sleep(800);
            }

            this.isRunning = false;
            this.showAnimation = false;
            this.showResults = true;

            this.calculateMetrics();
            this.generateInsights();

            // สร้าง charts
            await this.$nextTick();
            this.createCharts();
        },

        /**
         * จำลองรายได้ 1 เดือน
         */
        async simulateMonth(month) {
            // คำนวณการเติบโตของทีม (3% ต่อเดือน)
            const growthFactor = 1 + (month * 0.03);
            const currentTeamSize = Math.floor(this.inputs.teamSize * growthFactor);
            const totalTeamSales = currentTeamSize * this.inputs.teamAvgSales;

            // คำนวณ PV
            const pvRate = parseFloat(this.settings.global_pv_rate || 1);
            const personalPv = this.inputs.personalSales * pvRate;
            const teamPv = totalTeamSales * pvRate;

            // ตัวคูณจาก Rank
            const rankMultiplier = this.selectedRank ? parseFloat(this.selectedRank.bonus_multiplier || 1) : 1;
            const rankCommissionRate = this.selectedRank ? parseFloat(this.selectedRank.commission_rate || 0) : 0;

            // คำนวณ Unilevel
            let unilevelCommission = this.calculateUnilevel(teamPv, rankMultiplier);

            // คำนวณ Binary
            let binaryCommission = this.calculateBinary(teamPv, rankMultiplier);

            // คำนวณ Personal Commission
            const personalCommission = (personalPv * rankCommissionRate) / 100;

            // รวม
            let totalIncome = personalCommission + unilevelCommission + binaryCommission;

            // Overpay Protection
            totalIncome = this.applyOverpayProtection(
                totalIncome,
                personalCommission,
                unilevelCommission,
                binaryCommission,
                this.inputs.personalSales + totalTeamSales
            );

            // อัพเดท current income (สำหรับ animation)
            this.currentIncome.personal = personalCommission;
            this.currentIncome.team = unilevelCommission + binaryCommission;
            this.currentIncome.total = totalIncome;

            // เก็บข้อมูลเดือนนี้
            const accumulated = this.monthlyData.reduce((sum, m) => sum + m.total, 0) + totalIncome;

            this.monthlyData.push({
                month,
                personalSales: this.inputs.personalSales,
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
        },

        /**
         * คำนวณ Unilevel Commission
         */
        calculateUnilevel(teamPv, multiplier) {
            const unilevelLevels = this.settings.unilevel_levels || [];
            const maxDepth = parseInt(this.settings.unilevel_max_depth || 10);

            if (!Array.isArray(unilevelLevels) || unilevelLevels.length === 0) {
                return 0;
            }

            // สร้าง distribution แบบ pyramid
            const distribution = this.generateDistribution(maxDepth);

            let commission = 0;

            unilevelLevels.forEach((level, index) => {
                if (index >= maxDepth) return;

                const percentage = level.percentage || 0;
                if (percentage > 0) {
                    const dist = distribution[index] || 0;
                    const levelPv = teamPv * dist;
                    const levelCommission = (levelPv * percentage) / 100;
                    commission += levelCommission;
                }
            });

            return commission * multiplier;
        },

        /**
         * คำนวณ Binary Commission
         */
        calculateBinary(teamPv, multiplier) {
            const pairCommission = parseFloat(this.settings.binary_pair_commission || 0);

            if (pairCommission <= 0) return 0;

            // จำลอง left/right leg (60/40)
            const leftLegPv = teamPv * 0.6;
            const rightLegPv = teamPv * 0.4;
            const weakLegPv = Math.min(leftLegPv, rightLegPv);

            // Pairing type
            const pairingType = this.settings.binary_pairing_type || '1:1';
            const pairRatio = pairingType === '2:1' ? 2 : 1;
            const pairsAvailable = Math.floor(weakLegPv / pairRatio);

            // Max pairs limit
            const maxPairsPerDay = parseInt(this.settings.binary_max_pairs_per_day || 0);
            let pairsToPay = pairsAvailable;
            if (maxPairsPerDay > 0) {
                pairsToPay = Math.min(pairsToPay, maxPairsPerDay);
            }

            let commission = pairsToPay * pairCommission * multiplier;

            // Max commission limit
            const maxCommissionPerDay = parseFloat(this.settings.binary_max_commission_per_day || 0);
            if (maxCommissionPerDay > 0) {
                commission = Math.min(commission, maxCommissionPerDay);
            }

            return commission;
        },

        /**
         * Overpay Protection
         */
        applyOverpayProtection(totalIncome, personal, unilevel, binary, totalSales) {
            const enabled = this.settings.overpay_protection_enabled !== false;
            const maxPercentage = parseFloat(this.settings.max_commission_percentage || 50);

            if (!enabled) return totalIncome;

            const maxAllowed = (totalSales * maxPercentage) / 100;

            if (totalIncome > maxAllowed) {
                const scaleFactor = maxAllowed / totalIncome;
                return personal + (unilevel * scaleFactor) + (binary * scaleFactor);
            }

            return totalIncome;
        },

        /**
         * สร้าง distribution แบบ pyramid
         */
        generateDistribution(maxLevels) {
            const dist = {};
            const baseDistribution = [0.40, 0.25, 0.15, 0.10, 0.05, 0.03, 0.02];
            let remaining = 1.0;

            for (let i = 0; i < maxLevels; i++) {
                if (i < baseDistribution.length) {
                    dist[i] = baseDistribution[i];
                } else {
                    dist[i] = remaining / (maxLevels - baseDistribution.length);
                }
                remaining -= dist[i];
                if (remaining < 0) remaining = 0;
            }

            return dist;
        },

        /**
         * อัพเดท progress bar
         */
        updateProgress(current, total) {
            this.progress = Math.floor((current / total) * 100);
        },

        /**
         * คำนวณ metrics
         */
        calculateMetrics() {
            const total = this.monthlyData.reduce((sum, m) => sum + m.total, 0);
            const avg = total / this.monthlyData.length;
            const best = Math.max(...this.monthlyData.map(m => m.total));

            let growthRate = 0;
            if (this.monthlyData.length > 1) {
                const first = this.monthlyData[0].total;
                const last = this.monthlyData[this.monthlyData.length - 1].total;
                growthRate = first > 0 ? ((last - first) / first) * 100 : 0;
            }

            this.metrics = {
                avgMonthly: avg,
                bestMonth: best,
                growthRate: growthRate,
                yearlyProjection: avg * 12,
                grandTotal: total,
            };
        },

        /**
         * สร้างคำแนะนำเชิงกลยุทธ์
         */
        generateInsights() {
            this.insights = [];

            // Insight 1: Rank ที่แนะนำ
            if (!this.selectedRank) {
                this.insights.push({
                    type: 'warning',
                    icon: '⭐',
                    title: 'แนะนำให้เลือกยศ',
                    message: 'คุณจะได้รับคอมมิชชั่นส่วนตัวและตัวคูณโบนัสเพิ่มขึ้น ทำให้รายได้สูงขึ้นมาก'
                });
            } else {
                const avgPersonal = this.monthlyData.reduce((sum, m) => sum + m.personal, 0) / this.monthlyData.length;
                const percentOfTotal = (avgPersonal / this.metrics.avgMonthly) * 100;

                if (percentOfTotal < 20) {
                    this.insights.push({
                        type: 'tip',
                        icon: '💼',
                        title: 'เพิ่มยอดขายส่วนตัว',
                        message: `คอมมิชชั่นส่วนตัวของคุณคิดเป็น ${percentOfTotal.toFixed(0)}% เท่านั้น ลองเพิ่มยอดขายส่วนตัวเพื่อรายได้ที่มั่นคงกว่า`
                    });
                }
            }

            // Insight 2: Team Size
            const teamGrowth = this.monthlyData.length > 1
                ? ((this.monthlyData[this.monthlyData.length - 1].teamSize - this.monthlyData[0].teamSize) / this.monthlyData[0].teamSize) * 100
                : 0;

            if (teamGrowth > 30) {
                this.insights.push({
                    type: 'success',
                    icon: '🚀',
                    title: 'การเติบโตดีเยี่ยม',
                    message: `ทีมของคุณเติบโต ${teamGrowth.toFixed(0)}%! เป็นสัญญาณที่ดีมาก รักษาจังหวะนี้ไว้`
                });
            } else if (teamGrowth < 10) {
                this.insights.push({
                    type: 'info',
                    icon: '👥',
                    title: 'โฟกัสการสร้างทีม',
                    message: 'ทีมเติบโตช้า ลองเพิ่มกิจกรรมสร้างทีมและฝึกอบรมสมาชิก เพื่อเร่งการเติบโต'
                });
            }

            // Insight 3: Binary vs Unilevel
            const totalBinary = this.monthlyData.reduce((sum, m) => sum + m.binary, 0);
            const totalUnilevel = this.monthlyData.reduce((sum, m) => sum + m.unilevel, 0);

            if (totalBinary > totalUnilevel * 1.5) {
                this.insights.push({
                    type: 'success',
                    icon: '⚖️',
                    title: 'Binary ทรงตัวดี',
                    message: 'ขาซ้าย-ขวาของคุณสมดุลดี ทำให้ได้ Binary สูง รักษาความสมดุลนี้ไว้'
                });
            } else if (totalUnilevel > totalBinary * 2) {
                this.insights.push({
                    type: 'tip',
                    icon: '🔄',
                    title: 'ปรับสมดุล Binary',
                    message: 'ลองวางทีมให้สมดุลระหว่างขาซ้าย-ขวา เพื่อเพิ่มรายได้จาก Binary'
                });
            }

            // Insight 4: Growth Strategy
            if (this.metrics.growthRate < 5) {
                this.insights.push({
                    type: 'warning',
                    icon: '📈',
                    title: 'เร่งการเติบโต',
                    message: 'อัตราการเติบโตต่ำ ลองเพิ่มกิจกรรมการตลาด ฝึกอบรมทีม และสร้างแรงจูงใจ'
                });
            }

            // Insight 5: Yearly Projection
            if (this.metrics.yearlyProjection > 500000) {
                this.insights.push({
                    type: 'success',
                    icon: '💎',
                    title: 'เป้าหมายระยะยาว',
                    message: `รายได้คาดการณ์ ฿${this.formatNumber(this.metrics.yearlyProjection)}/ปี เยี่ยมมาก! รักษาสม่ำเสมอและวางแผนภาษี`
                });
            }
        },

        /**
         * สร้าง Charts
         */
        createCharts() {
            // Income Growth Chart
            const ctx1 = document.getElementById('income-chart');
            if (this.incomeChart) this.incomeChart.destroy();

            this.incomeChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: this.monthlyData.map(m => `เดือน ${m.month}`),
                    datasets: [{
                        label: 'รายได้รวม',
                        data: this.monthlyData.map(m => m.total),
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
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '฿' + this.formatNumber(value)
                            }
                        }
                    }
                }
            });

            // Breakdown Pie Chart
            const ctx2 = document.getElementById('breakdown-chart');
            if (this.breakdownChart) this.breakdownChart.destroy();

            const totalPersonal = this.monthlyData.reduce((sum, m) => sum + m.personal, 0);
            const totalUnilevel = this.monthlyData.reduce((sum, m) => sum + m.unilevel, 0);
            const totalBinary = this.monthlyData.reduce((sum, m) => sum + m.binary, 0);

            this.breakdownChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Com.ส่วนตัว', 'Unilevel', 'Binary'],
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
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    return context.label + ': ฿' + this.formatNumber(context.parsed);
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * รีเซ็ต
         */
        resetSimulation() {
            this.monthlyData = [];
            this.isRunning = false;
            this.showAnimation = false;
            this.showResults = false;
            this.currentMonth = 0;
            this.progress = 0;
            this.currentIncome = { personal: 0, team: 0, total: 0 };
            this.metrics = { avgMonthly: 0, bestMonth: 0, growthRate: 0, yearlyProjection: 0, grandTotal: 0 };
            this.insights = [];
        },

        /**
         * พิมพ์ผลลัพธ์
         */
        printResults() {
            window.print();
        },

        /**
         * แสดง tooltip
         */
        showTooltip(type) {
            const tooltips = {
                personal: {
                    title: 'ยอดขายของคุณ',
                    content: '<p class="mb-2"><strong>คือ:</strong> ยอดขายที่คุณทำเองแต่ละเดือน</p>' +
                            '<p class="mb-2"><strong>ใช้เพื่อ:</strong> คำนวณค่าคอมมิชชั่นส่วนตัว (Personal Commission) ตามเปอร์เซ็นต์ของยศที่เลือก</p>' +
                            '<p class="text-sm text-blue-600 dark:text-blue-400"><strong>💡 เคล็ดลับ:</strong> ยิ่งยอดขายส่วนตัวสูง รายได้ก็จะมั่นคงและไม่ขึ้นกับทีมเพียงอย่างเดียว</p>'
                },
                team: {
                    title: 'จำนวนสมาชิกในทีม',
                    content: '<p class="mb-2"><strong>คือ:</strong> จำนวนสมาชิกทั้งหมดในทีมของคุณ (ทุกชั้น)</p>' +
                            '<p class="mb-2"><strong>ใช้เพื่อ:</strong> คำนวณยอดขายรวมของทีม และค่าคอมมิชชั่น Unilevel + Binary</p>' +
                            '<p class="text-sm text-blue-600 dark:text-blue-400"><strong>💡 เคล็ดลับ:</strong> ทีมจะเติบโต 3% ต่อเดือนโดยอัตโนมัติในการจำลอง</p>'
                },
                avg: {
                    title: 'ยอดขายเฉลี่ยต่อคน',
                    content: '<p class="mb-2"><strong>คือ:</strong> ยอดขายเฉลี่ยที่แต่ละคนในทีมทำได้ต่อเดือน</p>' +
                            '<p class="mb-2"><strong>ใช้เพื่อ:</strong> คำนวณยอดขายรวมทั้งทีม = จำนวนคน × ยอดขายเฉลี่ย</p>' +
                            '<p class="text-sm text-blue-600 dark:text-blue-400"><strong>💡 เคล็ดลับ:</strong> ค่านี้สะท้อนคุณภาพทีม ฝึกอบรมทีมให้ขายเก่ง จะเพิ่มรายได้ทั้งทีม</p>'
                },
                months: {
                    title: 'ระยะเวลา',
                    content: '<p class="mb-2"><strong>คือ:</strong> จำนวนเดือนที่ต้องการจำลอง (1-60 เดือน)</p>' +
                            '<p class="mb-2"><strong>ใช้เพื่อ:</strong> ดูการเติบโตระยะยาว และวางแผนเป้าหมาย</p>' +
                            '<p class="text-sm text-blue-600 dark:text-blue-400"><strong>💡 เคล็ดลับ:</strong> แนะนำ 12-24 เดือนเพื่อดูภาพชัดเจน</p>'
                },
            };

            if (tooltips[type]) {
                this.tooltip = {
                    show: true,
                    ...tooltips[type]
                };
            }
        },

        /**
         * Format number
         */
        formatNumber(num) {
            return Math.floor(num).toLocaleString();
        },

        /**
         * Sleep
         */
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        /**
         * Default settings (fallback)
         */
        getDefaultSettings() {
            return {
                global_pv_rate: 1,
                unilevel_max_depth: 10,
                unilevel_levels: [],
                binary_pair_commission: 100,
                binary_pairing_type: '1:1',
                binary_max_pairs_per_day: null,
                binary_max_commission_per_day: null,
                overpay_protection_enabled: true,
                max_commission_percentage: 50,
            };
        },
    };
}

// Export for global use
window.incomeSimulator = incomeSimulator;
</script>
@endpush
@endsection
