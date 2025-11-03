@extends('layouts.admin')

@section('title', 'สร้างแผน MLM - Premium Configuration')

@push('styles')
<style>
    .premium-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .glass-effect {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .warning-danger {
        animation: pulse-danger 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse-danger {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        }
        50% {
            box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
        }
    }

    .stat-card {
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .placement-visual {
        position: relative;
        width: 100%;
        height: 200px;
        background: #f3f4f6;
        border-radius: 12px;
        overflow: hidden;
    }

    .node {
        position: absolute;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        color: white;
        transition: all 0.3s ease;
    }

    .node-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    .node-inactive {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    }

    .connection-line {
        position: absolute;
        height: 2px;
        background: #9ca3af;
        transform-origin: left center;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Header -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        🎯 สร้างแผน MLM - Premium Configuration
                    </h1>
                    <p class="text-gray-600 mt-2 text-lg">
                        ระบบการตั้งค่าอัจฉริยะ พร้อมการป้องกัน Overpay แบบเรียลไทม์
                    </p>
                </div>
                <a href="{{ route('admin.mlm.plans.index') }}"
                   class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-all duration-200 font-semibold">
                    ← กลับ
                </a>
            </div>
        </div>

        <form action="{{ route('admin.mlm.plans.store') }}" method="POST" id="mlm-plan-form">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Configuration -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Basic Information -->
                    <div class="glass-effect rounded-2xl shadow-xl p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-3 text-white">1</span>
                            ข้อมูลพื้นฐาน
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    ชื่อแผน (EN) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="plan-name" value="{{ old('name') }}"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                       placeholder="e.g., Diamond Plan" required>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    ชื่อแผน (TH)
                                </label>
                                <input type="text" name="name_th" value="{{ old('name_th') }}"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                       placeholder="เช่น แผนไดมอนด์">
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                ประเภทแผน <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-4">
                                <label class="plan-type-card cursor-pointer">
                                    <input type="radio" name="type" value="unilevel" class="hidden peer" required>
                                    <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-purple-500 peer-checked:bg-purple-50 transition-all text-center">
                                        <div class="text-4xl mb-2">📊</div>
                                        <div class="font-bold text-gray-800">Unilevel</div>
                                        <div class="text-xs text-gray-600 mt-1">ระดับชั้นสายงาน</div>
                                    </div>
                                </label>

                                <label class="plan-type-card cursor-pointer">
                                    <input type="radio" name="type" value="binary" class="hidden peer">
                                    <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-purple-500 peer-checked:bg-purple-50 transition-all text-center">
                                        <div class="text-4xl mb-2">🔀</div>
                                        <div class="font-bold text-gray-800">Binary</div>
                                        <div class="text-xs text-gray-600 mt-1">ทวิภาค (ซ้าย-ขวา)</div>
                                    </div>
                                </label>

                                <label class="plan-type-card cursor-pointer">
                                    <input type="radio" name="type" value="hybrid" class="hidden peer">
                                    <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-purple-500 peer-checked:bg-purple-50 transition-all text-center">
                                        <div class="text-4xl mb-2">⚡</div>
                                        <div class="font-bold text-gray-800">Hybrid</div>
                                        <div class="text-xs text-gray-600 mt-1">ผสม (ทั้งสองแบบ)</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                สีของแผน
                            </label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="color" id="plan-color" value="{{ old('color', '#8B5CF6') }}"
                                       class="w-20 h-12 border-2 border-gray-200 rounded-xl cursor-pointer">
                                <span id="color-preview" class="px-4 py-2 rounded-lg text-white font-semibold" style="background: #8B5CF6;">
                                    Preview
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- PV System -->
                    <div class="glass-effect rounded-2xl shadow-xl p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mr-3 text-white">2</span>
                            ระบบ PV (Point Value)
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    PV Rate (1 THB = X PV) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="global_pv_rate" id="pv-rate" value="{{ old('global_pv_rate', 1) }}"
                                       min="0" step="0.01"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">อัตราแปลงเงินเป็น PV</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Commission Per PV (THB) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="global_commission_per_pv" id="commission-per-pv" value="{{ old('global_commission_per_pv', 1) }}"
                                       min="0" step="0.01"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">ค่าคอมมิชชันต่อ 1 PV</p>
                            </div>
                        </div>
                    </div>

                    <!-- Unilevel Settings -->
                    <div id="unilevel-section" class="glass-effect rounded-2xl shadow-xl p-6 hidden">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3 text-white">3</span>
                            การตั้งค่า Unilevel
                        </h2>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-4">
                                เปอร์เซ็นต์คอมมิชชันแต่ละระดับ
                            </label>
                            <div id="unilevel-levels" class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    จำนวนระดับสูงสุด
                                </label>
                                <input type="number" name="unilevel_max_depth" id="unilevel-max-depth" value="{{ old('unilevel_max_depth', 10) }}"
                                       min="1" max="50"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Max Commission/Level (฿)
                                </label>
                                <input type="number" name="unilevel_max_commission_per_level" value="{{ old('unilevel_max_commission_per_level') }}"
                                       min="0" step="0.01"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                       placeholder="ไม่จำกัด">
                            </div>
                        </div>
                    </div>

                    <!-- Binary Settings -->
                    <div id="binary-section" class="glass-effect rounded-2xl shadow-xl p-6 hidden">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center mr-3 text-white">4</span>
                            การตั้งค่า Binary
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Pair Commission (฿/คู่)
                                </label>
                                <input type="number" name="binary_pair_commission" id="binary-pair-commission" value="{{ old('binary_pair_commission', 100) }}"
                                       min="0" step="0.01"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Match Percentage (%)
                                </label>
                                <input type="number" name="binary_match_percentage" id="binary-match-percentage" value="{{ old('binary_match_percentage', 50) }}"
                                       min="0" max="100" step="0.01"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Max Pairs/Day
                                </label>
                                <input type="number" name="binary_max_pairs_per_day" value="{{ old('binary_max_pairs_per_day') }}"
                                       min="0" step="1"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                                       placeholder="ไม่จำกัด">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Max Commission/Day (฿)
                                </label>
                                <input type="number" name="binary_max_commission_per_day" value="{{ old('binary_max_commission_per_day') }}"
                                       min="0" step="0.01"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                                       placeholder="ไม่จำกัด">
                            </div>
                        </div>

                        <!-- Flush Options -->
                        <div class="mt-6 p-4 bg-purple-50 rounded-xl">
                            <h3 class="font-bold text-gray-800 mb-4">การล้างคะแนน PV (Flush)</h3>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Flush Mode
                                    </label>
                                    <select name="binary_flush_mode" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500">
                                        <option value="percentage">Percentage (ตาม %)</option>
                                        <option value="full">Full (ล้างหมด)</option>
                                        <option value="none">None (ไม่ล้าง)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        Flush Percentage (%)
                                    </label>
                                    <input type="number" name="binary_flush_percentage" value="{{ old('binary_flush_percentage', 100) }}"
                                           min="0" max="100" step="0.01"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>

                            <label class="flex items-center">
                                <input type="checkbox" name="binary_carry_forward_pv" value="1" class="w-5 h-5 text-purple-600 rounded">
                                <span class="ml-3 text-sm font-semibold text-gray-700">ยกยอด PV ไปเดือนถัดไป (Carry Forward)</span>
                            </label>
                        </div>

                        <!-- Auto Placement -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-xl">
                            <h3 class="font-bold text-gray-800 mb-4">การต่อสายงานอัตโนมัติ (Auto Placement)</h3>

                            <label class="flex items-center mb-4">
                                <input type="checkbox" name="auto_placement" id="auto-placement-toggle" value="1" class="w-5 h-5 text-blue-600 rounded">
                                <span class="ml-3 text-sm font-semibold text-gray-700">เปิดใช้งาน Auto Placement</span>
                            </label>

                            <div id="placement-options" class="hidden">
                                <label class="block text-sm font-bold text-gray-700 mb-3">
                                    กลยุทธ์การจัดวาง
                                </label>

                                <div class="grid grid-cols-3 gap-3">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="binary_placement_preference" value="left_first" class="hidden peer">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                                            <div class="text-2xl mb-1">⬅️</div>
                                            <div class="text-xs font-bold">ซ้ายสุด</div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="binary_placement_preference" value="right_first" class="hidden peer">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                                            <div class="text-2xl mb-1">➡️</div>
                                            <div class="text-xs font-bold">ขวาสุด</div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="binary_placement_preference" value="fill_level" class="hidden peer">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                                            <div class="text-2xl mb-1">📊</div>
                                            <div class="text-xs font-bold">เต็มชั้น</div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="binary_placement_preference" value="weak_leg" class="hidden peer">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                                            <div class="text-2xl mb-1">⚖️</div>
                                            <div class="text-xs font-bold">ขาอ่อน</div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="binary_placement_preference" value="balanced" class="hidden peer">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                                            <div class="text-2xl mb-1">🎯</div>
                                            <div class="text-xs font-bold">สมดุล</div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Visual Preview -->
                                <div class="mt-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        ภาพตัวอย่างการจัดวาง
                                    </label>
                                    <div id="placement-visual" class="placement-visual"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="glass-effect rounded-2xl shadow-xl p-6">
                        <div class="flex items-center justify-between">
                            <button type="button" onclick="history.back()"
                                    class="px-8 py-4 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold transition-all">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                    class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold shadow-lg transition-all transform hover:scale-105">
                                💾 บันทึกแผน MLM
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Calculator -->
                <div class="lg:col-span-1">
                    <div class="sticky top-4 space-y-6">
                        <!-- Calculator -->
                        <div class="glass-effect rounded-2xl shadow-xl p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">📊 ตัวอย่างการคำนวณ</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        ยอดขาย (THB)
                                    </label>
                                    <input type="number" id="calc-sales" value="10000" min="0" step="100"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div id="calc-results" class="space-y-3">
                                    <!-- Results will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Overpay Warning -->
                        <div id="overpay-warning" class="hidden">
                            <div class="glass-effect rounded-2xl shadow-xl p-6 border-2 border-red-500 warning-danger">
                                <div class="flex items-start">
                                    <div class="text-4xl mr-3">⚠️</div>
                                    <div>
                                        <h3 class="text-xl font-bold text-red-600 mb-2">คำเตือน: Overpay!</h3>
                                        <p class="text-sm text-red-700 mb-3" id="overpay-message"></p>
                                        <div class="bg-red-100 rounded-lg p-3">
                                            <div class="text-xs font-semibold text-red-800 mb-1">อัตราคอมมิชชันรวม:</div>
                                            <div class="text-2xl font-bold text-red-600" id="total-commission-percentage">0%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Success Indicator -->
                        <div id="safe-indicator" class="hidden">
                            <div class="glass-effect rounded-2xl shadow-xl p-6 border-2 border-green-500">
                                <div class="flex items-center">
                                    <div class="text-4xl mr-3">✅</div>
                                    <div>
                                        <h3 class="text-xl font-bold text-green-600">การตั้งค่าปลอดภัย</h3>
                                        <p class="text-sm text-green-700">อัตราคอมมิชชันอยู่ในเกณฑ์ที่ดี</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tips -->
                        <div class="glass-effect rounded-2xl shadow-xl p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-3">💡 คำแนะนำ</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    <span>คอมมิชชันรวมไม่ควรเกิน 40-50% ของยอดขาย</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    <span>Binary ควรมี ceiling เพื่อป้องกัน overpay</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    <span>Flush PV ช่วยสร้างแรงจูงใจให้ซื้อต่อเนื่อง</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    <span>Auto Placement แบบ Balanced ช่วยสร้างทีมที่แข็งแรง</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('mlm-plan-form');
    const planTypeInputs = document.querySelectorAll('input[name="type"]');
    const unilevelSection = document.getElementById('unilevel-section');
    const binarySection = document.getElementById('binary-section');
    const autoPlacementToggle = document.getElementById('auto-placement-toggle');
    const placementOptions = document.getElementById('placement-options');
    const colorInput = document.getElementById('plan-color');
    const colorPreview = document.getElementById('color-preview');

    // Plan Type Change
    planTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            const type = this.value;

            unilevelSection.classList.add('hidden');
            binarySection.classList.add('hidden');

            if (type === 'unilevel' || type === 'hybrid') {
                unilevelSection.classList.remove('hidden');
                generateUnilevelLevels();
            }

            if (type === 'binary' || type === 'hybrid') {
                binarySection.classList.remove('hidden');
            }

            calculateCommission();
        });
    });

    // Color Preview
    colorInput.addEventListener('input', function() {
        colorPreview.style.background = this.value;
    });

    // Auto Placement Toggle
    if (autoPlacementToggle) {
        autoPlacementToggle.addEventListener('change', function() {
            if (this.checked) {
                placementOptions.classList.remove('hidden');
                visualizePlacement('left_first');
            } else {
                placementOptions.classList.add('hidden');
            }
        });
    }

    // Placement Strategy Change
    document.querySelectorAll('input[name="binary_placement_preference"]').forEach(input => {
        input.addEventListener('change', function() {
            visualizePlacement(this.value);
        });
    });

    // Generate Unilevel Levels
    function generateUnilevelLevels() {
        const container = document.getElementById('unilevel-levels');
        const maxDepth = parseInt(document.getElementById('unilevel-max-depth')?.value || 10);

        container.innerHTML = '';

        for (let i = 1; i <= Math.min(maxDepth, 10); i++) {
            const defaultValue = i <= 5 ? 10 - (i - 1) * 2 : 1;

            container.innerHTML += `
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-2">Level ${i}</label>
                    <div class="relative">
                        <input type="number" name="unilevel_levels[${i}]"
                               value="${defaultValue}" min="0" max="100" step="0.1"
                               class="w-full px-3 py-2 pr-8 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 unilevel-percentage">
                        <span class="absolute right-2 top-2 text-gray-500 text-sm">%</span>
                    </div>
                </div>
            `;
        }

        // Add event listeners to new inputs
        container.querySelectorAll('.unilevel-percentage').forEach(input => {
            input.addEventListener('input', calculateCommission);
        });
    }

    // Calculate Commission
    function calculateCommission() {
        const sales = parseFloat(document.getElementById('calc-sales')?.value || 10000);
        const pvRate = parseFloat(document.getElementById('pv-rate')?.value || 1);
        const commissionPerPv = parseFloat(document.getElementById('commission-per-pv')?.value || 1);
        const type = document.querySelector('input[name="type"]:checked')?.value;

        const pv = sales * pvRate;
        let totalCommission = 0;
        let totalPercentage = 0;
        let results = '<div class="text-sm font-semibold text-gray-700 mb-2">PV: ' + pv.toFixed(2) + '</div>';

        // Unilevel Calculation
        if (type === 'unilevel' || type === 'hybrid') {
            const levels = document.querySelectorAll('.unilevel-percentage');
            let unilevelTotal = 0;

            levels.forEach((input, index) => {
                const percentage = parseFloat(input.value || 0);
                const commission = (pv * commissionPerPv * percentage) / 100;
                unilevelTotal += commission;
                totalPercentage += percentage;
            });

            totalCommission += unilevelTotal;
            results += `
                <div class="bg-green-50 rounded-lg p-3 mb-2">
                    <div class="text-xs font-semibold text-green-800">Unilevel:</div>
                    <div class="text-lg font-bold text-green-600">฿${unilevelTotal.toFixed(2)}</div>
                </div>
            `;
        }

        // Binary Calculation
        if (type === 'binary' || type === 'hybrid') {
            const pairCommission = parseFloat(document.getElementById('binary-pair-commission')?.value || 100);
            const matchPercentage = parseFloat(document.getElementById('binary-match-percentage')?.value || 50);

            // Assume weak leg = 50% of total PV
            const weakLegPv = pv * 0.5;
            const pairs = Math.floor(weakLegPv);
            const binaryCommission = pairs * pairCommission;

            totalCommission += binaryCommission;
            totalPercentage += (binaryCommission / sales) * 100;

            results += `
                <div class="bg-orange-50 rounded-lg p-3 mb-2">
                    <div class="text-xs font-semibold text-orange-800">Binary (${pairs} pairs):</div>
                    <div class="text-lg font-bold text-orange-600">฿${binaryCommission.toFixed(2)}</div>
                </div>
            `;
        }

        // Total
        results += `
            <div class="bg-purple-50 rounded-lg p-3 border-2 border-purple-200">
                <div class="text-xs font-semibold text-purple-800">รวมทั้งหมด:</div>
                <div class="text-2xl font-bold text-purple-600">฿${totalCommission.toFixed(2)}</div>
                <div class="text-xs text-purple-700 mt-1">(${totalPercentage.toFixed(2)}% ของยอดขาย)</div>
            </div>
        `;

        document.getElementById('calc-results').innerHTML = results;

        // Overpay Check
        const overpayWarning = document.getElementById('overpay-warning');
        const safeIndicator = document.getElementById('safe-indicator');
        const totalPercentageDisplay = document.getElementById('total-commission-percentage');

        if (totalPercentage > 50) {
            overpayWarning.classList.remove('hidden');
            safeIndicator.classList.add('hidden');
            totalPercentageDisplay.textContent = totalPercentage.toFixed(2) + '%';
            document.getElementById('overpay-message').textContent =
                `อัตราคอมมิชชันรวม ${totalPercentage.toFixed(2)}% สูงกว่า 50% ซึ่งอาจทำให้เกิด Overpay และขาดทุน`;
        } else if (totalPercentage > 40) {
            overpayWarning.classList.remove('hidden');
            safeIndicator.classList.add('hidden');
            totalPercentageDisplay.textContent = totalPercentage.toFixed(2) + '%';
            document.getElementById('overpay-message').textContent =
                `อัตราคอมมิชชันรวม ${totalPercentage.toFixed(2)}% ใกล้เข้าขีดจำกัด ควรระมัดระวัง`;
            overpayWarning.querySelector('.glass-effect').style.borderColor = '#f59e0b';
        } else {
            overpayWarning.classList.add('hidden');
            safeIndicator.classList.remove('hidden');
        }
    }

    // Visualize Placement Strategy
    function visualizePlacement(strategy) {
        const visual = document.getElementById('placement-visual');
        if (!visual) return;

        visual.innerHTML = '';

        // Create simple binary tree visual
        const nodes = [
            { x: 50, y: 10, label: 'YOU', active: true },
            { x: 25, y: 40, label: 'L1', active: false },
            { x: 75, y: 40, label: 'R1', active: false },
            { x: 12.5, y: 70, label: 'L2', active: false },
            { x: 37.5, y: 70, label: 'L3', active: false },
            { x: 62.5, y: 70, label: 'R2', active: false },
            { x: 87.5, y: 70, label: 'R3', active: false },
        ];

        // Draw connections
        const connections = [
            { from: 0, to: 1 },
            { from: 0, to: 2 },
            { from: 1, to: 3 },
            { from: 1, to: 4 },
            { from: 2, to: 5 },
            { from: 2, to: 6 },
        ];

        connections.forEach(conn => {
            const from = nodes[conn.from];
            const to = nodes[conn.to];
            const line = document.createElement('div');
            line.className = 'connection-line';

            const dx = (to.x - from.x) * visual.offsetWidth / 100;
            const dy = (to.y - from.y) * visual.offsetHeight / 100;
            const length = Math.sqrt(dx * dx + dy * dy);
            const angle = Math.atan2(dy, dx) * 180 / Math.PI;

            line.style.left = from.x + '%';
            line.style.top = from.y + '%';
            line.style.width = length + 'px';
            line.style.transform = `rotate(${angle}deg)`;

            visual.appendChild(line);
        });

        // Highlight based on strategy
        let highlightIndices = [];
        switch(strategy) {
            case 'left_first':
                highlightIndices = [1, 3, 4];
                break;
            case 'right_first':
                highlightIndices = [2, 5, 6];
                break;
            case 'fill_level':
                highlightIndices = [1, 2];
                break;
            case 'balanced':
                highlightIndices = [1, 2];
                break;
            case 'weak_leg':
                highlightIndices = [1];
                break;
        }

        // Draw nodes
        nodes.forEach((node, index) => {
            const nodeDiv = document.createElement('div');
            nodeDiv.className = 'node ' + (node.active || highlightIndices.includes(index) ? 'node-active' : 'node-inactive');
            nodeDiv.style.left = `calc(${node.x}% - 25px)`;
            nodeDiv.style.top = `calc(${node.y}% - 25px)`;
            nodeDiv.textContent = node.label;

            visual.appendChild(nodeDiv);
        });
    }

    // Event listeners for calculator
    document.getElementById('calc-sales')?.addEventListener('input', calculateCommission);
    document.getElementById('pv-rate')?.addEventListener('input', calculateCommission);
    document.getElementById('commission-per-pv')?.addEventListener('input', calculateCommission);
    document.getElementById('binary-pair-commission')?.addEventListener('input', calculateCommission);
    document.getElementById('binary-match-percentage')?.addEventListener('input', calculateCommission);

    // Initial calculation
    setTimeout(calculateCommission, 500);
});
</script>
@endsection
