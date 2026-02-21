@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบ AI Gen')

@section('content')
<div x-data="aiGenSettings()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-cog text-purple-500 mr-2"></i>ตั้งค่าระบบ AI Gen
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">จัดการ Wallet, ราคา, โปรโมชั่น และการตั้งค่าทั่วไป</p>
        </div>
        <button @click="saveAll()" :disabled="saving"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold text-sm shadow transition disabled:opacity-50">
            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกทั้งหมด'"></span>
        </button>
    </div>

    {{-- แท็บ --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
        <template x-for="tab in settingsTabs" :key="tab.id">
            <button @click="activeSettingsTab = tab.id"
                :class="activeSettingsTab === tab.id
                    ? 'bg-purple-600 text-white shadow-lg'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium text-sm transition whitespace-nowrap">
                <i :class="tab.icon"></i>
                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    {{-- ======== แท็บ: Wallet & Pricing ======== --}}
    <div x-show="activeSettingsTab === 'wallet'" x-transition class="space-y-6">
        {{-- เปิด/ปิด Wallet --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-wallet text-green-500 mr-2"></i>การชำระเงินผ่าน Wallet
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เปิดให้ผู้ใช้จ่ายเงินจาก Wallet เพื่อสร้างภาพ/วิดีโอ</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="settings.wallet_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-600 peer-checked:bg-purple-600"></div>
                </label>
            </div>

            <div x-show="settings.wallet_enabled" x-transition class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-image text-blue-500 mr-1"></i> ราคาต่อภาพ (THB)
                        </label>
                        <input type="number" x-model.number="settings.wallet_cost_image" step="0.01" min="0"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p class="text-xs text-gray-400 mt-1">ราคาเริ่มต้นสำหรับทุก Provider (ตั้งแยกต่อ Provider ได้ด้านล่าง)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-video text-pink-500 mr-1"></i> ราคาต่อวิดีโอ (THB)
                        </label>
                        <input type="number" x-model.number="settings.wallet_cost_video" step="0.01" min="0"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p class="text-xs text-gray-400 mt-1">ราคาเริ่มต้นสำหรับทุก Provider</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ราคาแยกต่อ Provider --}}
        <div x-show="settings.wallet_enabled" x-transition
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-tags text-orange-500 mr-2"></i>ราคาแยกต่อ Provider
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">ตั้งราคาเฉพาะแต่ละ Provider (ถ้าไม่กำหนด จะใช้ราคาเริ่มต้นด้านบน)</p>

            <div class="space-y-3">
                @foreach($providers ?? [] as $provider)
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-3 w-full sm:w-48 flex-shrink-0">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-fuchsia-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-microchip text-white text-xs"></i>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white text-sm">{{ $provider->name }}</span>
                            <span class="block text-xs text-gray-400">{{ $provider->slug }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-1 w-full">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">ภาพ (฿)</label>
                            <input type="number"
                                x-model.number="providerPricing['{{ $provider->id }}'].wallet_cost_per_image"
                                step="0.01" min="0" placeholder="ใช้ค่าเริ่มต้น"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">วิดีโอ (฿)</label>
                            <input type="number"
                                x-model.number="providerPricing['{{ $provider->id }}'].wallet_cost_per_video"
                                step="0.01" min="0" placeholder="ใช้ค่าเริ่มต้น"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ======== แท็บ: โปรโมชั่น ======== --}}
    <div x-show="activeSettingsTab === 'promotions'" x-transition class="space-y-6">
        {{-- ปุ่มสร้างโปรโมชั่น --}}
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                <i class="fas fa-gift text-pink-500 mr-2"></i>โปรโมชั่น
            </h3>
            <button @click="showPromoModal = true; resetPromoForm()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-medium text-sm transition">
                <i class="fas fa-plus"></i> สร้างโปรโมชั่น
            </button>
        </div>

        {{-- รายการโปรโมชั่น --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ชื่อ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">มูลค่า</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">โค้ด</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ใช้ไป</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="promo in promotionsList" :key="promo.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm" x-text="promo.name"></div>
                                    <div class="text-xs text-gray-400" x-text="promo.applies_to === 'all' ? 'ทั้งหมด' : (promo.applies_to === 'image' ? 'ภาพ' : 'วิดีโอ')"></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300" x-text="promoTypeLabel(promo.type)"></td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white" x-text="promoValueLabel(promo)"></td>
                                <td class="px-4 py-3">
                                    <template x-if="promo.code">
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-mono rounded" x-text="promo.code"></span>
                                    </template>
                                    <template x-if="!promo.code">
                                        <span class="text-xs text-gray-400">อัตโนมัติ</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-300">
                                    <span x-text="promo.used_count"></span>
                                    <span class="text-gray-400" x-text="promo.max_uses ? '/' + promo.max_uses : ''"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="promo.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                                        x-text="promo.is_active ? 'เปิด' : 'ปิด'"></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="editPromotion(promo)"
                                            class="w-8 h-8 flex items-center justify-center text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                            <i class="fas fa-edit text-xs"></i>
                                        </button>
                                        <button @click="deletePromotion(promo)"
                                            class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="promotionsList.length === 0" class="text-center py-12 text-gray-400">
                <i class="fas fa-gift text-3xl mb-3"></i>
                <p class="text-sm">ยังไม่มีโปรโมชั่น</p>
            </div>
        </div>
    </div>

    {{-- ======== แท็บ: ตั้งค่าทั่วไป ======== --}}
    <div x-show="activeSettingsTab === 'general'" x-transition class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-sliders-h text-blue-500 mr-2"></i>ตั้งค่าทั่วไป
            </h3>

            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <span class="font-medium text-gray-900 dark:text-white text-sm">เปิดใช้งานระบบ AI Gen</span>
                    <p class="text-xs text-gray-400">ปิดเพื่อปิดระบบ AI Gen ทั้งหมดชั่วคราว</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="settings.system_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนสร้างสูงสุดต่อวัน</label>
                    <input type="number" x-model.number="settings.max_daily_generations" min="0"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความยาว Prompt สูงสุด</label>
                    <input type="number" x-model.number="settings.max_prompt_length" min="10"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider เริ่มต้น</label>
                <select x-model="settings.default_provider"
                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">เลือกอัตโนมัติ (ตามลำดับ priority)</option>
                    @foreach($providers ?? [] as $provider)
                    <option value="{{ $provider->slug }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- การจัดการพื้นที่เก็บภาพ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-hdd text-teal-500 mr-2"></i>จำกัดพื้นที่เก็บภาพ
            </h3>

            {{-- แสดงพื้นที่ใช้งานปัจจุบัน --}}
            <div class="bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 border border-teal-200 dark:border-teal-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-teal-700 dark:text-teal-300">
                        <i class="fas fa-database mr-1"></i>พื้นที่ใช้งาน
                    </span>
                    <span class="text-sm font-bold text-teal-800 dark:text-teal-200" x-text="storageUsed + ' / ' + (settings.max_storage_mb > 0 ? settings.max_storage_mb + ' MB' : 'ไม่จำกัด')"></span>
                </div>
                <div class="w-full bg-teal-200 dark:bg-teal-800 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500"
                         :class="storagePercent > 90 ? 'bg-red-500' : storagePercent > 70 ? 'bg-yellow-500' : 'bg-teal-500'"
                         :style="'width: ' + Math.min(storagePercent, 100) + '%'"></div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-teal-600 dark:text-teal-400" x-text="storageFileCount + ' ไฟล์'"></span>
                    <span class="text-xs font-medium" :class="storagePercent > 90 ? 'text-red-600 dark:text-red-400' : 'text-teal-600 dark:text-teal-400'" x-text="storagePercent + '%'"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-hdd text-teal-500 mr-1"></i>พื้นที่สูงสุด (MB)
                    </label>
                    <input type="number" x-model.number="settings.max_storage_mb" min="0" step="100"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">กำหนด 0 = ไม่จำกัดพื้นที่ (ค่าเริ่มต้น: 500 MB)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-broom text-orange-500 mr-1"></i>ลบภาพเก่าอัตโนมัติ (วัน)
                    </label>
                    <input type="number" x-model.number="settings.auto_cleanup_days" min="0"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">กำหนด 0 = ไม่ลบอัตโนมัติ (ค่าเริ่มต้น: 30 วัน)</p>
                </div>
            </div>

            {{-- ปุ่มจัดการพื้นที่ --}}
            <div class="flex flex-wrap gap-3 pt-2">
                <button @click="refreshStorageInfo()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-100 dark:bg-teal-900/30 hover:bg-teal-200 dark:hover:bg-teal-800/50 text-teal-700 dark:text-teal-300 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-sync-alt"></i> รีเฟรชข้อมูลพื้นที่
                </button>
                <button @click="cleanupOldFiles()" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-100 dark:bg-orange-900/30 hover:bg-orange-200 dark:hover:bg-orange-800/50 text-orange-700 dark:text-orange-300 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-broom"></i> ล้างภาพเก่า
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Modal สร้าง/แก้ไขโปรโมชั่น ===== --}}
    <div x-show="showPromoModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showPromoModal = false"></div>
        <div class="relative w-full max-w-xl max-h-[85vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            @click.stop>
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="editingPromo ? 'แก้ไขโปรโมชั่น' : 'สร้างโปรโมชั่นใหม่'"></h3>
                <button @click="showPromoModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อโปรโมชั่น *</label>
                    <input type="text" x-model="promoForm.name" required
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="เช่น: ส่วนลด 50% สำหรับปีใหม่">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท *</label>
                        <select x-model="promoForm.type"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="discount_percent">ส่วนลด (%)</option>
                            <option value="discount_fixed">ส่วนลด (บาท)</option>
                            <option value="free_credits">เครดิตฟรี</option>
                            <option value="bonus_credits">เครดิตโบนัส</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">มูลค่า *</label>
                        <input type="number" x-model.number="promoForm.value" step="0.01" min="0"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            :placeholder="promoForm.type === 'discount_percent' ? 'เช่น 50 (%)' : 'จำนวนเงิน/เครดิต'">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โค้ด (ถ้ามี)</label>
                        <input type="text" x-model="promoForm.code"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="เช่น AIGEN50">
                        <p class="text-xs text-gray-400 mt-1">เว้นว่างจะเป็นโปรโมชั่นอัตโนมัติ</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ใช้กับ</label>
                        <select x-model="promoForm.applies_to"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="all">ทั้งหมด</option>
                            <option value="image">ภาพเท่านั้น</option>
                            <option value="video">วิดีโอเท่านั้น</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำกัดจำนวนครั้ง</label>
                        <input type="number" x-model.number="promoForm.max_uses" min="1"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="ไม่จำกัด">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำกัดต่อผู้ใช้</label>
                        <input type="number" x-model.number="promoForm.max_uses_per_user" min="1"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="ไม่จำกัด">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันเริ่มต้น</label>
                        <input type="datetime-local" x-model="promoForm.starts_at"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันหมดอายุ</label>
                        <input type="datetime-local" x-model="promoForm.expires_at"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center justify-between py-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="promoForm.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 dark:border-gray-700">
                <button @click="showPromoModal = false" class="px-5 py-2.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition">ยกเลิก</button>
                <button @click="savePromotion()" :disabled="savingPromo"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50">
                    <i :class="savingPromo ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                    <span x-text="savingPromo ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 right-6 z-[60] max-w-sm">
        <div :class="{'bg-green-600': toast.type==='success', 'bg-red-600': toast.type==='error', 'bg-blue-600': toast.type==='info'}"
            class="flex items-center gap-3 px-5 py-3 rounded-xl text-white shadow-2xl">
            <i :class="{'fas fa-check-circle': toast.type==='success', 'fas fa-exclamation-circle': toast.type==='error'}"></i>
            <span class="text-sm" x-text="toast.message"></span>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับหน้าตั้งค่า AI Gen
 */
function aiGenSettings() {
    return {
        activeSettingsTab: 'wallet',
        saving: false,
        savingPromo: false,
        showPromoModal: false,
        editingPromo: null,
        toast: { show: false, message: '', type: 'info' },

        settingsTabs: [
            { id: 'wallet', label: 'Wallet & ราคา', icon: 'fas fa-wallet' },
            { id: 'promotions', label: 'โปรโมชั่น', icon: 'fas fa-gift' },
            { id: 'general', label: 'ทั่วไป', icon: 'fas fa-sliders-h' }
        ],

        // ข้อมูลพื้นที่เก็บภาพ
        storageUsed: '{{ $storageInfo["used_display"] ?? "0 MB" }}',
        storagePercent: {{ $storageInfo['percent'] ?? 0 }},
        storageFileCount: {{ $storageInfo['file_count'] ?? 0 }},

        settings: {
            wallet_enabled: {{ json_encode($settings['wallet_enabled'] ?? false) }},
            wallet_cost_image: {{ $settings['wallet_cost_image'] ?? 5 }},
            wallet_cost_video: {{ $settings['wallet_cost_video'] ?? 20 }},
            system_enabled: {{ json_encode($settings['system_enabled'] ?? true) }},
            max_daily_generations: {{ $settings['max_daily_generations'] ?? 100 }},
            max_prompt_length: {{ $settings['max_prompt_length'] ?? 1000 }},
            allow_nsfw: {{ json_encode($settings['allow_nsfw'] ?? false) }},
            default_provider: '{{ $settings['default_provider'] ?? '' }}',
            max_storage_mb: {{ $settings['max_storage_mb'] ?? 500 }},
            auto_cleanup_days: {{ $settings['auto_cleanup_days'] ?? 30 }}
        },

        providerPricing: {
            @foreach($providers ?? [] as $provider)
            '{{ $provider->id }}': {
                wallet_cost_per_image: {{ $provider->wallet_cost_per_image ?? 'null' }},
                wallet_cost_per_video: {{ $provider->wallet_cost_per_video ?? 'null' }}
            },
            @endforeach
        },

        promotionsList: @json($promotions ?? []),

        promoForm: {
            name: '', type: 'discount_percent', value: 0, code: '',
            applies_to: 'all', max_uses: null, max_uses_per_user: null,
            starts_at: '', expires_at: '', is_active: true
        },

        init() {
            // โหลดข้อมูลเพิ่มเติมถ้าจำเป็น
        },

        // ===== บันทึกตั้งค่า =====
        async saveAll() {
            this.saving = true;
            try {
                const payload = {
                    ai_gen_wallet_enabled: this.settings.wallet_enabled,
                    ai_gen_wallet_cost_image: this.settings.wallet_cost_image,
                    ai_gen_wallet_cost_video: this.settings.wallet_cost_video,
                    ai_gen_system_enabled: this.settings.system_enabled,
                    ai_gen_max_daily_generations: this.settings.max_daily_generations,
                    ai_gen_max_prompt_length: this.settings.max_prompt_length,
                    ai_gen_allow_nsfw: this.settings.allow_nsfw,
                    ai_gen_default_provider: this.settings.default_provider,
                    ai_gen_max_storage_mb: this.settings.max_storage_mb,
                    ai_gen_auto_cleanup_days: this.settings.auto_cleanup_days,
                    provider_pricing: this.providerPricing
                };

                const res = await fetch('{{ route("admin.ai-gen.settings.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (data.success) {
                    this.showToast('บันทึกตั้งค่าสำเร็จ', 'success');
                } else {
                    this.showToast(data.error || 'บันทึกไม่สำเร็จ', 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด: ' + e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        // ===== โปรโมชั่น =====
        resetPromoForm() {
            this.editingPromo = null;
            this.promoForm = {
                name: '', type: 'discount_percent', value: 0, code: '',
                applies_to: 'all', max_uses: null, max_uses_per_user: null,
                starts_at: '', expires_at: '', is_active: true
            };
        },

        editPromotion(promo) {
            this.editingPromo = promo;
            this.promoForm = { ...promo };
            this.showPromoModal = true;
        },

        async savePromotion() {
            this.savingPromo = true;
            try {
                const url = this.editingPromo
                    ? '{{ url("admin/ai-gen/promotions") }}/' + this.editingPromo.id
                    : '{{ route("admin.ai-gen.promotions.store") }}';
                const method = this.editingPromo ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(this.promoForm)
                });

                const data = await res.json();

                if (data.success) {
                    this.showPromoModal = false;
                    this.showToast(this.editingPromo ? 'อัพเดทโปรโมชั่นสำเร็จ' : 'สร้างโปรโมชั่นสำเร็จ', 'success');
                    // อัพเดทรายการ
                    if (this.editingPromo) {
                        const idx = this.promotionsList.findIndex(p => p.id === this.editingPromo.id);
                        if (idx !== -1) this.promotionsList[idx] = data.data;
                    } else {
                        this.promotionsList.unshift(data.data);
                    }
                } else {
                    this.showToast(data.error || 'บันทึกไม่สำเร็จ', 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            } finally {
                this.savingPromo = false;
            }
        },

        async deletePromotion(promo) {
            if (!confirm('ต้องการลบโปรโมชั่น "' + promo.name + '" หรือไม่?')) return;
            try {
                const res = await fetch('{{ url("admin/ai-gen/promotions") }}/' + promo.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.promotionsList = this.promotionsList.filter(p => p.id !== promo.id);
                    this.showToast('ลบโปรโมชั่นสำเร็จ', 'success');
                }
            } catch (e) {
                this.showToast('ลบไม่สำเร็จ', 'error');
            }
        },

        // ===== จัดการพื้นที่เก็บภาพ =====
        async refreshStorageInfo() {
            try {
                this.showToast('🔄 กำลังคำนวณพื้นที่...', 'info');
                const res = await fetch('{{ route("admin.ai-gen.storage.info") }}', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
                });
                const data = await res.json();
                if (data.success) {
                    this.storageUsed = data.data.used_display;
                    this.storagePercent = data.data.percent;
                    this.storageFileCount = data.data.file_count;
                    this.showToast('✅ อัพเดทข้อมูลพื้นที่สำเร็จ', 'success');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async cleanupOldFiles() {
            const days = this.settings.auto_cleanup_days || 30;
            if (!confirm('ต้องการลบภาพที่เก่ากว่า ' + days + ' วัน?')) return;
            try {
                this.showToast('🧹 กำลังล้างภาพเก่า...', 'info');
                const res = await fetch('{{ route("admin.ai-gen.storage.cleanup") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ days: days })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast('✅ ลบภาพเก่าสำเร็จ ' + data.deleted_count + ' ไฟล์ (' + data.freed_display + ')', 'success');
                    await this.refreshStorageInfo();
                } else {
                    this.showToast(data.error || 'ลบไม่สำเร็จ', 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        // Helpers
        promoTypeLabel(type) {
            return { 'discount_percent': 'ส่วนลด %', 'discount_fixed': 'ส่วนลดบาท', 'free_credits': 'เครดิตฟรี', 'bonus_credits': 'โบนัส' }[type] || type;
        },
        promoValueLabel(promo) {
            if (promo.type === 'discount_percent') return promo.value + '%';
            if (promo.type === 'discount_fixed') return '฿' + Number(promo.value).toLocaleString();
            return promo.value + ' เครดิต';
        },
        showToast(message, type = 'info') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        }
    }
}
</script>
@endpush
