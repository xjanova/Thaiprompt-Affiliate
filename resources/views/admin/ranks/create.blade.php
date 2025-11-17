@extends('layouts.admin-v3')

@section('title', 'สร้าง Rank ใหม่')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200">
                    🏆 สร้าง Rank ใหม่
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">
                    กำหนดรายละเอียดและข้อกำหนดสำหรับ Rank ใหม่
                </p>
            </div>
            <a href="{{ route('admin.ranks.index') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl transition-all duration-200">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.ranks.store') }}" method="POST">
        @csrf

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 space-y-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4 flex items-center">
                    <span class="mr-2">📝</span>
                    ข้อมูลพื้นฐาน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            ชื่อ Rank (EN) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               placeholder="e.g., Bronze, Silver, Gold" required>
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            ชื่อ Rank (TH)
                        </label>
                        <input type="text" name="name_th" value="{{ old('name_th') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               placeholder="เช่น ทองแดง, เงิน, ทอง">
                        @error('name_th')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Level <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="level" value="{{ old('level', 1) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">ระดับของ Rank (ตัวเลขที่สูงกว่า = ระดับที่สูงกว่า)</p>
                        @error('level')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            สี <span class="text-red-500">*</span>
                        </label>
                        <input type="color" name="color" value="{{ old('color', '#3B82F6') }}"
                               class="w-full h-10 px-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">สีที่แสดงสำหรับ Rank นี้</p>
                        @error('color')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            จำนวนดาว <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="stars" value="{{ old('stars', 1) }}" min="1" max="10"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">จำนวนดาวที่แสดงในผังสายงาน (1-10)</p>
                        @error('stars')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Icon URL
                        </label>
                        <input type="text" name="icon" value="{{ old('icon') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               placeholder="/images/ranks/bronze.svg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">URL หรือ path ของไอคอน (เช่น /images/ranks/bronze.svg)</p>
                        @error('icon')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Badge Icon (Emoji)
                        </label>
                        <input type="text" name="badge_icon" value="{{ old('badge_icon') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               placeholder="🥉"
                               maxlength="10">
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">อิโมจิหรือไอคอนตราสำหรับ Badge (เช่น 🥉, 🥈, 🥇)</p>
                        @error('badge_icon')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        คำอธิบาย (EN)
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                              placeholder="Description of this rank...">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        คำอธิบาย (TH)
                    </label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                              placeholder="คำอธิบายของ Rank นี้...">{{ old('description_th') }}</textarea>
                    @error('description_th')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Benefits -->
            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4 flex items-center">
                    <span class="mr-2">💰</span>
                    ผลประโยชน์และสิทธิพิเศษ
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Commission Rate (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="commission_rate" value="{{ old('commission_rate', 5) }}"
                               min="0" max="100" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">เปอร์เซ็นต์ค่าคอมมิชชั่นที่ได้รับ</p>
                        @error('commission_rate')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Bonus Multiplier <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="bonus_multiplier" value="{{ old('bonus_multiplier', 1) }}"
                               min="1" step="0.1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">ตัวคูณโบนัสพิเศษ</p>
                        @error('bonus_multiplier')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Requirements -->
            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-200 mb-4 flex items-center">
                    <span class="mr-2">📋</span>
                    ข้อกำหนด
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            <span class="mr-1">💰</span> ยอดขายขั้นต่ำ (฿) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="min_sales" value="{{ old('min_sales', 0) }}"
                               min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        @error('min_sales')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            <span class="mr-1">👥</span> Referrals ขั้นต่ำ <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="min_referrals" value="{{ old('min_referrals', 0) }}"
                               min="0"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        @error('min_referrals')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            <span class="mr-1">⭐</span> Points ขั้นต่ำ <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="min_points" value="{{ old('min_points', 0) }}"
                               min="0"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:text-gray-200"
                               required>
                        @error('min_points')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.ranks.index') }}"
                       class="px-6 py-2 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl transition-all duration-200">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        สร้าง Rank
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
