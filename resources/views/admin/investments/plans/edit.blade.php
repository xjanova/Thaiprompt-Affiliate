@extends('layouts.admin-v3')

@section('title', 'แก้ไขแผนการลงทุน')

@push('styles')
<style>
    .investment-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #10b981 100%);
    }

    .form-glow:focus {
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        border-color: #f59e0b;
    }

    .dark .form-glow:focus {
        box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.2);
        border-color: #d97706;
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
    selectedIcon: '{{ $plan->icon ?? 'fas fa-chart-line' }}',
    selectedColor: '{{ $plan->color ?? '#f59e0b' }}',
    showDeleteModal: false,
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
    <div class="mb-8 relative overflow-hidden rounded-2xl investment-gradient p-8 shadow-2xl">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('admin.investments.plans.index') }}"
                   class="mr-4 text-white hover:text-yellow-300 transition-colors">
                    <i class="fas fa-arrow-left text-2xl"></i>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-white flex items-center">
                        <i class="fas fa-edit mr-3 text-yellow-300"></i>
                        แก้ไขแผนการลงทุน
                    </h2>
                    <p class="text-white/90 text-lg mt-1">{{ $plan->name_th ?? $plan->name }}</p>
                </div>
            </div>

            <button type="button" @click="showDeleteModal = true"
                    class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                <i class="fas fa-trash mr-2"></i>ลบแผน
            </button>
        </div>
    </div>

    <form action="{{ route('admin.investments.plans.update', $plan) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
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
                            <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                            @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Name TH -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อแผน (ภาษาไทย)
                            </label>
                            <input type="text" name="name_th" value="{{ old('name_th', $plan->name_th) }}"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                            @error('name_th')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description EN -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย (ภาษาอังกฤษ)
                            </label>
                            <textarea name="description" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">{{ old('description', $plan->description) }}</textarea>
                            @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description TH -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย (ภาษาไทย)
                            </label>
                            <textarea name="description_th" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">{{ old('description_th', $plan->description_th) }}</textarea>
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
                                <input type="number" name="min_amount" value="{{ old('min_amount', $plan->min_amount) }}" required step="0.01"
                                       class="w-full pl-8 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
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
                                <input type="number" name="max_amount" value="{{ old('max_amount', $plan->max_amount) }}" step="0.01"
                                       class="w-full pl-8 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากไม่จำกัด</p>
                            @error('max_amount')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- ROI and Terms -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
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
                                <input type="number" name="roi_rate" value="{{ old('roi_rate', $plan->roi_rate) }}" required step="0.01"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
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
                            <select name="roi_frequency" required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                                <option value="daily" {{ old('roi_frequency', $plan->roi_frequency) === 'daily' ? 'selected' : '' }}>รายวัน</option>
                                <option value="weekly" {{ old('roi_frequency', $plan->roi_frequency) === 'weekly' ? 'selected' : '' }}>รายสัปดาห์</option>
                                <option value="monthly" {{ old('roi_frequency', $plan->roi_frequency) === 'monthly' ? 'selected' : '' }}>รายเดือน</option>
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
                            <input type="number" name="term_days" value="{{ old('term_days', $plan->term_days) }}" required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                            @error('term_days')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Lock Days -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ระยะเวลาล็อค (วัน) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="lock_days" value="{{ old('lock_days', $plan->lock_days) }}" required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
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
                                <input type="number" name="early_withdrawal_penalty" value="{{ old('early_withdrawal_penalty', $plan->early_withdrawal_penalty) }}" step="0.01"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
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
                                <input type="number" name="referral_bonus_rate" value="{{ old('referral_bonus_rate', $plan->referral_bonus_rate) }}" step="0.01"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                                <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">%</span>
                            </div>
                            @error('referral_bonus_rate')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
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
                                    <input type="checkbox" name="allow_compound" {{ old('allow_compound', $plan->allow_compound) ? 'checked' : '' }} class="sr-only peer">
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
                                    <input type="checkbox" name="allow_early_withdrawal" {{ old('allow_early_withdrawal', $plan->allow_early_withdrawal) ? 'checked' : '' }} class="sr-only peer">
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
                                    <input type="checkbox" name="is_active" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="sr-only peer">
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
                                    <input type="checkbox" name="is_featured" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }} class="sr-only peer">
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
                                    <input type="checkbox" name="requires_kyc" {{ old('requires_kyc', $plan->requires_kyc) ? 'checked' : '' }} class="sr-only peer">
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
                                <input type="number" name="max_investors" value="{{ old('max_investors', $plan->max_investors) }}"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากไม่จำกัด</p>
                            </div>

                            <!-- Required Rank -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    ระดับสมาชิกที่ต้องการ
                                </label>
                                <select name="required_rank_id"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
                                    <option value="">ไม่จำกัด</option>
                                    @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}" {{ old('required_rank_id', $plan->required_rank_id) == $rank->id ? 'selected' : '' }}>{{ $rank->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Sort Order -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    ลำดับการแสดง
                                </label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white form-glow focus:outline-none transition-all">
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
                </div>
            </div>

            <!-- Sidebar Preview -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 sticky top-24">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-eye mr-2 text-amber-500"></i>
                        ตัวอย่าง
                    </h3>

                    <div class="bg-gradient-to-br from-amber-50 to-green-50 dark:from-gray-700 dark:to-gray-700 rounded-xl p-6 border-2 border-amber-200 dark:border-amber-700">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-amber-400 to-green-500 flex items-center justify-center mr-3">
                                <i :class="selectedIcon" class="text-white text-xl"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white">{{ $plan->name_th ?? $plan->name }}</div>
                                @if($plan->is_featured)
                                <span class="text-xs px-2 py-1 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full">แนะนำ</span>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">ROI:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ $plan->roi_rate }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">ระยะเวลา:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $plan->term_days }} วัน</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">ขั้นต่ำ:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($plan->min_amount, 0) }}</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400">สถานะ:</div>
                            @if($plan->is_active)
                            <span class="inline-flex items-center text-xs font-semibold text-green-600 dark:text-green-400">
                                <i class="fas fa-check-circle mr-1"></i> เปิดใช้งาน
                            </span>
                            @else
                            <span class="inline-flex items-center text-xs font-semibold text-gray-600 dark:text-gray-400">
                                <i class="fas fa-pause-circle mr-1"></i> ปิดใช้งาน
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-2">
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-green-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-green-600 transition-all duration-300 shadow-lg transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                 @click="showDeleteModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                            ยืนยันการลบแผนการลงทุน
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                คุณแน่ใจหรือไม่ว่าต้องการลบแผนการลงทุนนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form action="{{ route('admin.investments.plans.destroy', $plan) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-200">
                            <i class="fas fa-trash mr-2"></i>ลบแผน
                        </button>
                    </form>
                    <button type="button"
                            @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:w-auto sm:text-sm transition-all duration-200">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
