@extends('layouts.admin')

@section('title', 'สร้างแผนการลงทุนใหม่')

@push('styles')
<style>
    .investment-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .investment-gradient-dark {
        background: linear-gradient(135deg, #d97706 0%, #059669 100%);
    }

    .form-glow:focus {
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        border-color: #f59e0b;
    }

    .dark .form-glow:focus {
        box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.2);
        border-color: #d97706;
    }

    .step-indicator {
        transition: all 0.3s ease;
    }

    .step-active {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .pulse-slow {
        animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.8;
        }
    }

    .icon-selector {
        cursor: pointer;
        transition: all 0.2s;
    }

    .icon-selector:hover {
        transform: scale(1.1);
    }

    .icon-selected {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
        color: white !important;
    }
</style>
@endpush

@section('content')
<div x-data="{
    currentStep: 1,
    selectedIcon: 'fas fa-chart-line',
    selectedColor: '#f59e0b',
    showIconPicker: false,
    form: {
        name: '',
        name_th: '',
        description: '',
        description_th: '',
        min_amount: '',
        max_amount: '',
        roi_rate: '',
        roi_frequency: 'daily',
        term_days: '',
        lock_days: '',
        allow_compound: false,
        allow_early_withdrawal: false,
        early_withdrawal_penalty: '',
        referral_bonus_rate: '',
        is_active: true,
        is_featured: false,
        max_investors: '',
        required_rank_id: '',
        requires_kyc: false,
        sort_order: 0
    },
    icons: [
        'fas fa-chart-line', 'fas fa-coins', 'fas fa-gem', 'fas fa-crown',
        'fas fa-trophy', 'fas fa-rocket', 'fas fa-star', 'fas fa-bolt',
        'fas fa-fire', 'fas fa-certificate', 'fas fa-medal', 'fas fa-award',
        'fas fa-piggy-bank', 'fas fa-money-bill-wave', 'fas fa-wallet', 'fas fa-hand-holding-usd'
    ],
    colors: [
        '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#ec4899',
        '#14b8a6', '#f97316', '#06b6d4', '#84cc16'
    ]
}">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <a href="{{ route('admin.investments.plans.index') }}"
               class="mr-4 text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-plus-circle mr-3 text-amber-500"></i>
                    สร้างแผนการลงทุนใหม่
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนดรายละเอียดและเงื่อนไขของแผนการลงทุน</p>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-center space-x-4 mb-8">
            <div class="flex items-center">
                <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                     :class="currentStep >= 1 ? 'step-active' : 'bg-gray-300 dark:bg-gray-600'">
                    1
                </div>
                <span class="ml-2 text-sm font-medium" :class="currentStep >= 1 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400'">ข้อมูลพื้นฐาน</span>
            </div>
            <div class="w-16 h-1 rounded" :class="currentStep >= 2 ? 'bg-gradient-to-r from-amber-500 to-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
            <div class="flex items-center">
                <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                     :class="currentStep >= 2 ? 'step-active' : 'bg-gray-300 dark:bg-gray-600'">
                    2
                </div>
                <span class="ml-2 text-sm font-medium" :class="currentStep >= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400'">ROI และระยะเวลา</span>
            </div>
            <div class="w-16 h-1 rounded" :class="currentStep >= 3 ? 'bg-gradient-to-r from-amber-500 to-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
            <div class="flex items-center">
                <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                     :class="currentStep >= 3 ? 'step-active' : 'bg-gray-300 dark:bg-gray-600'">
                    3
                </div>
                <span class="ml-2 text-sm font-medium" :class="currentStep >= 3 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400'">การตั้งค่าเพิ่มเติม</span>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.investments.plans.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Step 1: Basic Information -->
        <div x-show="currentStep === 1" x-transition class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-info-circle mr-3 text-blue-500"></i>
                ข้อมูลพื้นฐาน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name EN -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อแผน (ภาษาอังกฤษ) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="form.name" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                           placeholder="Gold Investment Plan">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name TH -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อแผน (ภาษาไทย)
                    </label>
                    <input type="text" name="name_th" x-model="form.name_th"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                           placeholder="แผนลงทุนทองคำ">
                    @error('name_th')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description EN -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย (ภาษาอังกฤษ)
                    </label>
                    <textarea name="description" x-model="form.description" rows="3"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                              placeholder="High returns with secure investment"></textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description TH -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย (ภาษาไทย)
                    </label>
                    <textarea name="description_th" x-model="form.description_th" rows="3"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                              placeholder="ผลตอบแทนสูงพร้อมความปลอดภัย"></textarea>
                    @error('description_th')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Min Amount -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนเงินขั้นต่ำ (บาท) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">฿</span>
                        <input type="number" name="min_amount" x-model="form.min_amount" required step="0.01"
                               class="w-full pl-8 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="1000">
                    </div>
                    @error('min_amount')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Amount -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนเงินสูงสุด (บาท)
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">฿</span>
                        <input type="number" name="max_amount" x-model="form.max_amount" step="0.01"
                               class="w-full pl-8 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="ไม่จำกัด">
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากไม่จำกัด</p>
                    @error('max_amount')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button" @click="currentStep = 2"
                        class="px-8 py-3 bg-gradient-to-r from-amber-500 to-green-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-green-600 transition-all duration-300 shadow-lg transform hover:scale-105">
                    ถัดไป <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: ROI and Terms -->
        <div x-show="currentStep === 2" x-transition class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-percentage mr-3 text-green-500"></i>
                ROI และระยะเวลา
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ROI Rate -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        อัตรา ROI (%) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="roi_rate" x-model="form.roi_rate" required step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="5.00">
                        <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    @error('roi_rate')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ROI Frequency -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ความถี่ในการจ่าย ROI <span class="text-red-500">*</span>
                    </label>
                    <select name="roi_frequency" x-model="form.roi_frequency" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                        <option value="daily">รายวัน</option>
                        <option value="weekly">รายสัปดาห์</option>
                        <option value="monthly">รายเดือน</option>
                    </select>
                    @error('roi_frequency')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Term Days -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลาแผน (วัน) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="term_days" x-model="form.term_days" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                           placeholder="30">
                    @error('term_days')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Lock Days -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลาล็อค (วัน) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="lock_days" x-model="form.lock_days" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                           placeholder="0">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนวันที่ไม่สามารถถอนเงินได้</p>
                    @error('lock_days')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Early Withdrawal Penalty -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ค่าปรับการถอนก่อนกำหนด (%)
                    </label>
                    <div class="relative">
                        <input type="number" name="early_withdrawal_penalty" x-model="form.early_withdrawal_penalty" step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="10.00">
                        <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    @error('early_withdrawal_penalty')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Referral Bonus Rate -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        อัตราโบนัสอ้างอิง (%)
                    </label>
                    <div class="relative">
                        <input type="number" name="referral_bonus_rate" x-model="form.referral_bonus_rate" step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="5.00">
                        <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">%</span>
                    </div>
                    @error('referral_bonus_rate')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" @click="currentStep = 1"
                        class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-300">
                    <i class="fas fa-arrow-left mr-2"></i> ย้อนกลับ
                </button>
                <button type="button" @click="currentStep = 3"
                        class="px-8 py-3 bg-gradient-to-r from-amber-500 to-green-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-green-600 transition-all duration-300 shadow-lg transform hover:scale-105">
                    ถัดไป <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Additional Settings -->
        <div x-show="currentStep === 3" x-transition class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-cog mr-3 text-purple-500"></i>
                การตั้งค่าเพิ่มเติม
            </h3>

            <div class="space-y-6">
                <!-- Toggles -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Allow Compound -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">อนุญาตการทบต้น</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">นำ ROI กลับมาลงทุนต่อ</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_compound" x-model="form.allow_compound" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-green-500"></div>
                        </label>
                    </div>

                    <!-- Allow Early Withdrawal -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">อนุญาตการถอนก่อนกำหนด</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">สามารถถอนก่อนครบกำหนด</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_early_withdrawal" x-model="form.allow_early_withdrawal" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-green-500"></div>
                        </label>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">แสดงแผนนี้ให้ผู้ใช้เห็น</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" x-model="form.is_active" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-green-500"></div>
                        </label>
                    </div>

                    <!-- Is Featured -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">แผนแนะนำ</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">แสดงเป็นแผนแนะนำพิเศษ</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_featured" x-model="form.is_featured" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-pink-500"></div>
                        </label>
                    </div>

                    <!-- Requires KYC -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ต้องยืนยัน KYC</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ผู้ใช้ต้องยืนยันตัวตน</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="requires_kyc" x-model="form.requires_kyc" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Additional Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Max Investors -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนนักลงทุนสูงสุด
                        </label>
                        <input type="number" name="max_investors" x-model="form.max_investors"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="ไม่จำกัด">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากไม่จำกัด</p>
                    </div>

                    <!-- Required Rank -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ระดับสมาชิกที่ต้องการ
                        </label>
                        <select name="required_rank_id" x-model="form.required_rank_id"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                            <option value="">ไม่จำกัด</option>
                            @foreach($ranks as $rank)
                            <option value="{{ $rank->id }}">{{ $rank->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ลำดับการแสดง
                        </label>
                        <input type="number" name="sort_order" x-model="form.sort_order"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all"
                               placeholder="0">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตัวเลขน้อยจะแสดงก่อน</p>
                    </div>
                </div>

                <!-- Icon and Color Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Icon Picker -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ไอคอน
                        </label>
                        <input type="hidden" name="icon" x-model="selectedIcon">
                        <div class="grid grid-cols-8 gap-2">
                            <template x-for="icon in icons" :key="icon">
                                <div @click="selectedIcon = icon"
                                     :class="selectedIcon === icon ? 'icon-selected' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                                     class="icon-selector w-10 h-10 rounded-lg flex items-center justify-center">
                                    <i :class="icon"></i>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Color Picker -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            สี
                        </label>
                        <input type="hidden" name="color" x-model="selectedColor">
                        <div class="grid grid-cols-10 gap-2">
                            <template x-for="color in colors" :key="color">
                                <div @click="selectedColor = color"
                                     :style="`background-color: ${color}`"
                                     class="icon-selector w-10 h-10 rounded-lg cursor-pointer border-2"
                                     :class="selectedColor === color ? 'border-gray-900 dark:border-white ring-2 ring-offset-2 ring-gray-900 dark:ring-white' : 'border-transparent'">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-8">
                <button type="button" @click="currentStep = 2"
                        class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-300">
                    <i class="fas fa-arrow-left mr-2"></i> ย้อนกลับ
                </button>
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-amber-500 to-green-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-green-600 transition-all duration-300 shadow-lg transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i> สร้างแผนการลงทุน
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
