@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900/50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 transition">
                        หน้าแรก
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ route('shipping-addresses.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 transition">
                        ที่อยู่จัดส่ง
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">เพิ่มที่อยู่ใหม่</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">📍 เพิ่มที่อยู่จัดส่งใหม่</h1>
            <p class="text-gray-600 dark:text-gray-400">กรุณากรอกข้อมูลที่อยู่จัดส่งของคุณ</p>
        </div>

        @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <form action="{{ route('shipping-addresses.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <!-- Recipient Name -->
                    <div>
                        <label for="recipient_name" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                            ชื่อผู้รับ <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="recipient_name"
                               name="recipient_name"
                               value="{{ old('recipient_name') }}"
                               required
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('recipient_name') border-red-500 @enderror"
                               placeholder="ชื่อ-นามสกุล ผู้รับสินค้า">
                        @error('recipient_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                            เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                        </label>
                        <input type="tel"
                               id="phone_number"
                               name="phone_number"
                               value="{{ old('phone_number') }}"
                               required
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('phone_number') border-red-500 @enderror"
                               placeholder="0812345678">
                        @error('phone_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address Line 1 -->
                    <div>
                        <label for="address_line_1" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                            ที่อยู่ (บ้านเลขที่ ถนน) <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="address_line_1"
                               name="address_line_1"
                               value="{{ old('address_line_1') }}"
                               required
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('address_line_1') border-red-500 @enderror"
                               placeholder="บ้านเลขที่ ซอย ถนน">
                        @error('address_line_1')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address Line 2 -->
                    <div>
                        <label for="address_line_2" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                            ที่อยู่เพิ่มเติม (ถ้ามี)
                        </label>
                        <input type="text"
                               id="address_line_2"
                               name="address_line_2"
                               value="{{ old('address_line_2') }}"
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('address_line_2') border-red-500 @enderror"
                               placeholder="หมู่บ้าน คอนโด อพาร์ทเมนท์">
                        @error('address_line_2')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Grid: Sub-district and District -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Sub-district -->
                        <div>
                            <label for="sub_district" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                                ตำบล/แขวง <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="sub_district"
                                   name="sub_district"
                                   value="{{ old('sub_district') }}"
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('sub_district') border-red-500 @enderror"
                                   placeholder="ตำบล/แขวง">
                            @error('sub_district')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- District -->
                        <div>
                            <label for="district" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                                อำเภอ/เขต <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="district"
                                   name="district"
                                   value="{{ old('district') }}"
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('district') border-red-500 @enderror"
                                   placeholder="อำเภอ/เขต">
                            @error('district')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Grid: Province and Postal Code -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Province -->
                        <div>
                            <label for="province" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                                จังหวัด <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="province"
                                   name="province"
                                   value="{{ old('province') }}"
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('province') border-red-500 @enderror"
                                   placeholder="จังหวัด">
                            @error('province')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <label for="postal_code" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                                รหัสไปรษณีย์ <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="postal_code"
                                   name="postal_code"
                                   value="{{ old('postal_code') }}"
                                   required
                                   maxlength="5"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('postal_code') border-red-500 @enderror"
                                   placeholder="10110">
                            @error('postal_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Country (Hidden, defaults to Thailand) -->
                    <input type="hidden" name="country" value="ประเทศไทย">

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                            หมายเหตุ (ถ้ามี)
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:border-indigo-600 focus:outline-none transition @error('notes') border-red-500 @enderror"
                                  placeholder="เช่น สถานที่ใกล้เคียง หรือข้อความเพิ่มเติม">{{ old('notes') }}</textarea>
                        @error('notes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Is Default -->
                    <div class="flex items-center">
                        <input type="checkbox"
                               id="is_default"
                               name="is_default"
                               value="1"
                               {{ old('is_default') ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                        <label for="is_default" class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                            ตั้งเป็นที่อยู่เริ่มต้น
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-center rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        ✅ บันทึกที่อยู่
                    </button>
                    <a href="{{ route('shipping-addresses.index') }}"
                       class="flex-1 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-600 text-gray-700 dark:text-gray-300 hover:text-indigo-600 font-semibold text-center rounded-xl transition">
                        ← ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
