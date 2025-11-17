{{--
/**
 * MLM Settings Tab สำหรับ Theme Customizer
 *
 * ตั้งค่า MLM System ผ่าน Theme Customizer โดยตรง
 */
--}}

<div x-show="activeTab === 'mlm'" class="space-y-6" x-data="{
    mlmSettings: {
        binaryPlacementStrategy: 'balanced',
        binaryEnabled: true,
        unilevelEnabled: true,
        autoPlacement: true,
    },

    init() {
        // โหลด MLM settings จาก localStorage
        const saved = localStorage.getItem('mlmSettings');
        if (saved) {
            this.mlmSettings = { ...this.mlmSettings, ...JSON.parse(saved) };
        }
        this.loadFromServer();
    },

    async loadFromServer() {
        try {
            const response = await fetch('/admin/mlm/settings/get');
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.mlmSettings.binaryPlacementStrategy = data.settings.auto_placement_type || 'balanced';
                    this.mlmSettings.binaryEnabled = data.settings.binary_enabled || false;
                    this.mlmSettings.unilevelEnabled = data.settings.unilevel_enabled || false;
                    this.mlmSettings.autoPlacement = data.settings.auto_placement || true;
                }
            }
        } catch (error) {
            console.error('Failed to load MLM settings:', error);
        }
    },

    async saveMlmSettings() {
        // บันทึกใน localStorage
        localStorage.setItem('mlmSettings', JSON.stringify(this.mlmSettings));

        // บันทึกไปยัง server
        try {
            const response = await fetch('/admin/mlm/settings/update-placement', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({
                    auto_placement_type: this.mlmSettings.binaryPlacementStrategy,
                    auto_placement: this.mlmSettings.autoPlacement,
                    binary_enabled: this.mlmSettings.binaryEnabled,
                    unilevel_enabled: this.mlmSettings.unilevelEnabled,
                }),
            });

            if (response.ok) {
                // แสดง notification
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        type: 'success',
                        message: 'บันทึกการตั้งค่า MLM เรียบร้อย'
                    }
                }));
            }
        } catch (error) {
            console.error('Failed to save MLM settings:', error);
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    type: 'error',
                    message: 'ไม่สามารถบันทึกการตั้งค่าได้'
                }
            }));
        }
    }
}">

    {{-- Header --}}
    <div class="flex items-center gap-3 pb-4 border-b border-white/20">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
            <i class="fas fa-sitemap text-white"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-white">การตั้งค่า MLM</h3>
            <p class="text-xs text-white/60">จัดการ Binary Placement และระบบคอมมิชชั่น</p>
        </div>
    </div>

    {{-- Binary Placement Strategy --}}
    <div class="bg-white/5 rounded-xl p-4 border border-white/20">
        <label class="flex items-center justify-between text-white text-sm font-medium mb-3">
            <span><i class="fas fa-sitemap mr-2"></i>กลยุทธ์การจัดเรียง Binary</span>
            <span class="text-xs px-2 py-1 bg-blue-500/20 text-blue-300 rounded-lg" x-text="mlmSettings.binaryPlacementStrategy"></span>
        </label>

        <div class="space-y-2">
            {{-- Left to Right --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                   :class="mlmSettings.binaryPlacementStrategy === 'left_to_right'
                       ? 'border-blue-400 bg-blue-500/10'
                       : 'border-white/10 hover:border-white/30 hover:bg-white/5'">
                <input type="radio" name="placementStrategy" value="left_to_right"
                       x-model="mlmSettings.binaryPlacementStrategy"
                       @change="saveMlmSettings()"
                       class="mt-1">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-arrow-right text-blue-300 text-sm"></i>
                        <span class="font-medium text-white text-sm">Left-to-Right (Sequential)</span>
                    </div>
                    <p class="text-xs text-white/60">เติมจากซ้ายไปขวาตามลำดับ - เหมาะกับการเติมแบบเรียงตามเวลา</p>
                </div>
            </label>

            {{-- Balanced --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                   :class="mlmSettings.binaryPlacementStrategy === 'balanced'
                       ? 'border-green-400 bg-green-500/10'
                       : 'border-white/10 hover:border-white/30 hover:bg-white/5'">
                <input type="radio" name="placementStrategy" value="balanced"
                       x-model="mlmSettings.binaryPlacementStrategy"
                       @change="saveMlmSettings()"
                       class="mt-1">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-balance-scale text-green-300 text-sm"></i>
                        <span class="font-medium text-white text-sm">Balanced (สมดุล)</span>
                        <span class="px-2 py-0.5 bg-green-500/20 text-green-300 text-[10px] rounded-full font-bold">แนะนำ</span>
                    </div>
                    <p class="text-xs text-white/60">จัดเรียงให้ทั้ง 2 ขาสมดุล - เหมาะกับการสร้างทีมที่แข็งแรงเท่าเทียม</p>
                </div>
            </label>

            {{-- Weak Leg --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                   :class="mlmSettings.binaryPlacementStrategy === 'weak_leg'
                       ? 'border-yellow-400 bg-yellow-500/10'
                       : 'border-white/10 hover:border-white/30 hover:bg-white/5'">
                <input type="radio" name="placementStrategy" value="weak_leg"
                       x-model="mlmSettings.binaryPlacementStrategy"
                       @change="saveMlmSettings()"
                       class="mt-1">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-chart-line text-yellow-300 text-sm"></i>
                        <span class="font-medium text-white text-sm">Weak Leg (แขนอ่อน)</span>
                    </div>
                    <p class="text-xs text-white/60">เติมขาที่มี PV น้อยกว่า - เหมาะกับการเพิ่ม PV ให้ขาอ่อน</p>
                </div>
            </label>

            {{-- Fill by Level (ใหม่!) --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                   :class="mlmSettings.binaryPlacementStrategy === 'fill_by_level'
                       ? 'border-purple-400 bg-purple-500/10'
                       : 'border-white/10 hover:border-white/30 hover:bg-white/5'">
                <input type="radio" name="placementStrategy" value="fill_by_level"
                       x-model="mlmSettings.binaryPlacementStrategy"
                       @change="saveMlmSettings()"
                       class="mt-1">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-layer-group text-purple-300 text-sm"></i>
                        <span class="font-medium text-white text-sm">Fill by Level (เติมให้เต็มชั้น)</span>
                        <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 text-[10px] rounded-full font-bold">ใหม่!</span>
                    </div>
                    <p class="text-xs text-white/60">เติมแต่ละชั้นให้เต็มก่อนไปชั้นถัดไป - เหมาะกับการสร้างโครงสร้างที่เป็นระเบียบ</p>
                </div>
            </label>
        </div>

        {{-- Visual Preview --}}
        <div class="mt-4 p-3 bg-white/5 rounded-lg border border-white/10">
            <div class="text-xs text-white/70 mb-2 flex items-center gap-2">
                <i class="fas fa-eye"></i>
                <span>ตัวอย่างการจัดเรียง</span>
            </div>
            <div class="flex justify-center">
                {{-- Simple Tree Visualization --}}
                <div class="text-center" x-show="mlmSettings.binaryPlacementStrategy === 'balanced'">
                    <div class="text-3xl mb-2">🎯</div>
                    <div class="flex justify-center gap-8 mb-2">
                        <div class="text-2xl">👤</div>
                        <div class="text-2xl">👤</div>
                    </div>
                    <div class="flex justify-center gap-4">
                        <div class="text-xl">👤</div>
                        <div class="text-xl">👤</div>
                        <div class="text-xl">👤</div>
                        <div class="text-xl">👤</div>
                    </div>
                    <p class="text-xs text-white/50 mt-2">สมดุลทั้ง 2 ขา</p>
                </div>

                <div class="text-xs text-white/50" x-show="mlmSettings.binaryPlacementStrategy !== 'balanced'">
                    <i class="fas fa-info-circle"></i> เลือก strategy เพื่อดูตัวอย่าง
                </div>
            </div>
        </div>
    </div>

    {{-- MLM System Toggles --}}
    <div class="space-y-3">
        {{-- Binary Enable/Disable --}}
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/20">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-code-branch text-blue-300"></i>
                    <span class="font-medium text-white text-sm">เปิดใช้งาน Binary</span>
                </div>
                <p class="text-xs text-white/60">คอมมิชชั่นแบบคู่ (Pair Matching)</p>
            </div>
            <button @click="mlmSettings.binaryEnabled = !mlmSettings.binaryEnabled; saveMlmSettings()"
                    class="relative w-14 h-7 rounded-full transition-colors duration-300"
                    :class="mlmSettings.binaryEnabled ? 'bg-green-500' : 'bg-gray-600'">
                <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow-lg"
                      :class="mlmSettings.binaryEnabled ? 'translate-x-7' : 'translate-x-0'"></span>
            </button>
        </div>

        {{-- Unilevel Enable/Disable --}}
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/20">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-layer-group text-purple-300"></i>
                    <span class="font-medium text-white text-sm">เปิดใช้งาน Unilevel</span>
                </div>
                <p class="text-xs text-white/60">คอมมิชชั่นแบบขั้นบันได (Level Commission)</p>
            </div>
            <button @click="mlmSettings.unilevelEnabled = !mlmSettings.unilevelEnabled; saveMlmSettings()"
                    class="relative w-14 h-7 rounded-full transition-colors duration-300"
                    :class="mlmSettings.unilevelEnabled ? 'bg-green-500' : 'bg-gray-600'">
                <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow-lg"
                      :class="mlmSettings.unilevelEnabled ? 'translate-x-7' : 'translate-x-0'"></span>
            </button>
        </div>

        {{-- Auto Placement --}}
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/20">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-magic text-yellow-300"></i>
                    <span class="font-medium text-white text-sm">จัดเรียงอัตโนมัติ</span>
                </div>
                <p class="text-xs text-white/60">ระบบจัดเรียงสมาชิกใหม่อัตโนมัติตาม strategy</p>
            </div>
            <button @click="mlmSettings.autoPlacement = !mlmSettings.autoPlacement; saveMlmSettings()"
                    class="relative w-14 h-7 rounded-full transition-colors duration-300"
                    :class="mlmSettings.autoPlacement ? 'bg-green-500' : 'bg-gray-600'">
                <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow-lg"
                      :class="mlmSettings.autoPlacement ? 'translate-x-7' : 'translate-x-0'"></span>
            </button>
        </div>
    </div>

    {{-- Quick Link to Full Settings --}}
    <div class="bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-xl p-4 border border-purple-400/30">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/30 flex items-center justify-center">
                    <i class="fas fa-cogs text-purple-300"></i>
                </div>
                <div>
                    <div class="font-medium text-white text-sm">ตั้งค่าเพิ่มเติม</div>
                    <div class="text-xs text-white/60">คอมมิชชั่น, PV, ขีดจำกัด และอื่นๆ</div>
                </div>
            </div>
            <a href="{{ route('admin.mlm.settings.index') }}"
               class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors text-sm font-medium">
                <i class="fas fa-external-link-alt mr-1"></i>
                เปิด
            </a>
        </div>
    </div>

</div>
