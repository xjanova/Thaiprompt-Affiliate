@extends('layouts.admin')

@section('title', 'สร้างแผน MLM ใหม่')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    📊 สร้างแผน MLM ใหม่
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    กำหนดค่าและสร้างแผน MLM ประเภท Unilevel, Binary หรือ Hybrid
                </p>
            </div>
            <a href="{{ route('admin.mlm.plans.index') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-all duration-200">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">ประเภทของแผน MLM</h3>
                <ul class="text-sm text-blue-700 mt-1 space-y-1">
                    <li>• <strong>Unilevel:</strong> รับค่าคอมมิชชั่นตามระดับชั้นของสายงาน (เช่น ระดับ 1-10)</li>
                    <li>• <strong>Binary:</strong> สายงานแบบทวิภาค จับคู่ซ้าย-ขวา รับค่าคอมมิชชั่นเมื่อจับคู่สำเร็จ</li>
                    <li>• <strong>Hybrid:</strong> รวมทั้ง Unilevel และ Binary เข้าด้วยกัน</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.mlm.plans.store') }}" method="POST">
        @csrf

        <div class="bg-white rounded-lg shadow-md p-6 space-y-6">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">📝</span>
                    ข้อมูลพื้นฐาน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อแผน (EN) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="e.g., Premium Plan" required>
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อแผน (TH)
                        </label>
                        <input type="text" name="name_th" value="{{ old('name_th') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="เช่น แผนพรีเมียม">
                        @error('name_th')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ประเภทแผน <span class="text-red-500">*</span>
                        </label>
                        <select name="type" id="plan-type-select"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                required>
                            <option value="">เลือกประเภท...</option>
                            <option value="unilevel" {{ old('type') === 'unilevel' ? 'selected' : '' }}>Unilevel (ระดับชั้นสายงาน)</option>
                            <option value="binary" {{ old('type') === 'binary' ? 'selected' : '' }}>Binary (ทวิภาค)</option>
                            <option value="hybrid" {{ old('type') === 'hybrid' ? 'selected' : '' }}>Hybrid (ผสม)</option>
                        </select>
                        @error('type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            สี
                        </label>
                        <input type="color" name="color" value="{{ old('color', '#8B5CF6') }}"
                               class="w-full h-10 px-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">สีที่ใช้แสดงในการ์ดแผน</p>
                        @error('color')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        คำอธิบาย (EN)
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="Description of this plan...">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        คำอธิบาย (TH)
                    </label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="คำอธิบายของแผนนี้...">{{ old('description_th') }}</textarea>
                    @error('description_th')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Joining Fee -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">💳</span>
                    ค่าสมัครสมาชิก
                </h3>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="requires_joining_fee" id="requires-joining-fee"
                               value="1"
                               {{ old('requires_joining_fee') ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">
                            ต้องการค่าสมัครสมาชิก
                        </span>
                    </label>
                </div>

                <div id="joining-fee-section" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        จำนวนเงิน (฿)
                    </label>
                    <input type="number" name="joining_fee" value="{{ old('joining_fee', 0) }}"
                           min="0" step="0.01"
                           class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">ค่าสมัครที่สมาชิกต้องจ่ายเพื่อเข้าร่วมแผนนี้</p>
                    @error('joining_fee')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- PV System -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">⭐</span>
                    ระบบ PV (Point Value)
                </h3>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="use_pv_system" id="use-pv-system"
                               value="1"
                               {{ old('use_pv_system', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">
                            ใช้ระบบ PV ในการคำนวณค่าคอมมิชชั่น
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 ml-6 mt-1">
                        PV (Point Value) คือคะแนนที่แปลงจากมูลค่าการซื้อ ใช้ในการคำนวณค่าคอมมิชชั่น
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Global PV Rate <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">1 THB =</span>
                            <input type="number" name="global_pv_rate" value="{{ old('global_pv_rate', 1) }}"
                                   min="0" step="0.01"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                   required>
                            <span class="text-sm text-gray-600">PV</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">อัตราการแปลงเงินเป็น PV (เช่น 1 THB = 1 PV)</p>
                        @error('global_pv_rate')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Commission Per PV <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="global_commission_per_pv" value="{{ old('global_commission_per_pv', 1) }}"
                                   min="0" step="0.01"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                   required>
                            <span class="text-sm text-gray-600">THB/PV</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">ค่าคอมมิชชั่นต่อ 1 PV (เช่น 1 PV = 1 THB)</p>
                        @error('global_commission_per_pv')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Unilevel Settings -->
            <div id="unilevel-settings" class="border-t border-gray-200 pt-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">📊</span>
                    การตั้งค่า Unilevel
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            จำนวนระดับสูงสุด
                        </label>
                        <input type="number" name="unilevel_max_depth" value="{{ old('unilevel_max_depth', 10) }}"
                               min="1" max="50"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">ระดับสูงสุดที่จะคำนวณค่าคอมมิชชั่น (1-50)</p>
                        @error('unilevel_max_depth')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center h-full">
                            <input type="checkbox" name="unilevel_compression" value="1"
                                   {{ old('unilevel_compression') ? 'checked' : '' }}
                                   class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <div class="ml-2">
                                <span class="text-sm text-gray-700 font-medium">เปิดใช้งาน Compression</span>
                                <p class="text-xs text-gray-500">ข้ามสมาชิกที่ไม่มีคุณสมบัติ</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        อัตราค่าคอมมิชชั่นแต่ละระดับ (%)
                    </label>
                    <div id="unilevel-levels-container" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        @for($i = 1; $i <= 10; $i++)
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">ระดับ {{ $i }}</label>
                            <input type="number" name="unilevel_levels[{{ $i }}]"
                                   value="{{ old("unilevel_levels.{$i}", $i <= 5 ? 10 - ($i - 1) * 2 : 0) }}"
                                   min="0" max="100" step="0.01"
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-purple-500">
                        </div>
                        @endfor
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        กำหนดเปอร์เซ็นต์ค่าคอมมิชชั่นสำหรับแต่ละระดับ (0-100%)
                    </p>
                    @error('unilevel_levels')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Binary Settings -->
            <div id="binary-settings" class="border-t border-gray-200 pt-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">🔀</span>
                    การตั้งค่า Binary
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pair Commission (฿)
                        </label>
                        <input type="number" name="binary_pair_commission" value="{{ old('binary_pair_commission', 100) }}"
                               min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">ค่าคอมมิชชั่นต่อ 1 คู่ที่จับคู่สำเร็จ</p>
                        @error('binary_pair_commission')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Match Percentage (%)
                        </label>
                        <input type="number" name="binary_match_percentage" value="{{ old('binary_match_percentage', 50) }}"
                               min="0" max="100" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">เปอร์เซ็นต์การจับคู่ (50% = ต้องมีทั้ง 2 ฝั่งเท่ากัน)</p>
                        @error('binary_match_percentage')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pairing Type
                        </label>
                        <select name="binary_pairing_type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="1:1" {{ old('binary_pairing_type') === '1:1' ? 'selected' : '' }}>1:1 (เท่ากัน)</option>
                            <option value="2:1" {{ old('binary_pairing_type') === '2:1' ? 'selected' : '' }}>2:1 (ขาหนักมากกว่า)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">อัตราส่วนการจับคู่</p>
                        @error('binary_pairing_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center h-full">
                            <input type="checkbox" name="binary_spillover" value="1"
                                   {{ old('binary_spillover', true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <div class="ml-2">
                                <span class="text-sm text-gray-700 font-medium">Spillover</span>
                                <p class="text-xs text-gray-500">อนุญาตให้สมาชิกล้นไปขาที่เต็ม</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Auto Placement -->
            <div id="auto-placement-section" class="border-t border-gray-200 pt-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">🎯</span>
                    Auto Placement (สำหรับ Binary)
                </h3>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="auto_placement" id="auto-placement"
                               value="1"
                               {{ old('auto_placement') ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">
                            เปิดใช้งาน Auto Placement
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 ml-6 mt-1">
                        วางตำแหน่งสมาชิกใหม่อัตโนมัติตามกลยุทธ์ที่เลือก
                    </p>
                </div>

                <div id="auto-placement-type-section" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        กลยุทธ์ Auto Placement
                    </label>
                    <select name="auto_placement_type"
                            class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="left_to_right" {{ old('auto_placement_type') === 'left_to_right' ? 'selected' : '' }}>
                            Left to Right (ซ้ายไปขวา)
                        </option>
                        <option value="balanced" {{ old('auto_placement_type') === 'balanced' ? 'selected' : '' }}>
                            Balanced (สมดุล)
                        </option>
                        <option value="weak_leg" {{ old('auto_placement_type') === 'weak_leg' ? 'selected' : '' }}>
                            Weak Leg (ขาที่อ่อนกว่า)
                        </option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">วิธีการจัดวางสมาชิกใหม่ในโครงสร้าง Binary</p>
                    @error('auto_placement_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="border-t border-gray-200 pt-6">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.mlm.plans.index') }}"
                       class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-all duration-200">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        สร้างแผน MLM
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- JavaScript for Dynamic Form -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const planTypeSelect = document.getElementById('plan-type-select');
    const unilevelSettings = document.getElementById('unilevel-settings');
    const binarySettings = document.getElementById('binary-settings');
    const autoPlacementSection = document.getElementById('auto-placement-section');
    const requiresJoiningFee = document.getElementById('requires-joining-fee');
    const joiningFeeSection = document.getElementById('joining-fee-section');
    const autoPlacement = document.getElementById('auto-placement');
    const autoPlacementTypeSection = document.getElementById('auto-placement-type-section');

    // Toggle sections based on plan type
    planTypeSelect.addEventListener('change', function() {
        const type = this.value;

        // Reset visibility
        unilevelSettings.classList.add('hidden');
        binarySettings.classList.add('hidden');
        autoPlacementSection.classList.add('hidden');

        // Show relevant sections
        if (type === 'unilevel' || type === 'hybrid') {
            unilevelSettings.classList.remove('hidden');
        }
        if (type === 'binary' || type === 'hybrid') {
            binarySettings.classList.remove('hidden');
            autoPlacementSection.classList.remove('hidden');
        }
    });

    // Toggle joining fee input
    if (requiresJoiningFee) {
        requiresJoiningFee.addEventListener('change', function() {
            if (this.checked) {
                joiningFeeSection.classList.remove('hidden');
            } else {
                joiningFeeSection.classList.add('hidden');
            }
        });

        // Initial state
        if (requiresJoiningFee.checked) {
            joiningFeeSection.classList.remove('hidden');
        }
    }

    // Toggle auto placement type
    if (autoPlacement) {
        autoPlacement.addEventListener('change', function() {
            if (this.checked) {
                autoPlacementTypeSection.classList.remove('hidden');
            } else {
                autoPlacementTypeSection.classList.add('hidden');
            }
        });

        // Initial state
        if (autoPlacement.checked) {
            autoPlacementTypeSection.classList.remove('hidden');
        }
    }

    // Trigger initial state
    if (planTypeSelect.value) {
        planTypeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
