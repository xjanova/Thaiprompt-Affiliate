@extends('layouts.admin-v3')

@section('title', 'แก้ไขผู้ติดต่อ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Modern Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <div class="relative">
            <!-- Language Switcher -->
            <div class="absolute top-0 right-0 z-10">
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span x-text="language === 'th' ? 'ไทย' : (language === 'en' ? 'English' : (language === 'zh' ? '中文' : (language === 'ja' ? '日本語' : language)))"></span>
                        <svg class="w-4 h-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border-2 border-white/20 dark:border-slate-700 overflow-hidden z-50">
                        <button @click="language = 'th'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'th' }">
                            <span class="text-2xl">🇹🇭</span>
                            <span class="font-semibold text-gray-900 dark:text-white">ไทย</span>
                        </button>
                        <button @click="language = 'en'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'en' }">
                            <span class="text-2xl">🇬🇧</span>
                            <span class="font-semibold text-gray-900 dark:text-white">English</span>
                        </button>
                        <button @click="language = 'zh'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'zh' }">
                            <span class="text-2xl">🇨🇳</span>
                            <span class="font-semibold text-gray-900 dark:text-white">中文</span>
                        </button>
                        <button @click="language = 'ja'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'ja' }">
                            <span class="text-2xl">🇯🇵</span>
                            <span class="font-semibold text-gray-900 dark:text-white">日本語</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 w-full pt-12 sm:pt-0">
                <div class="flex items-center gap-6">
                    <!-- Back Button -->
                    <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                       class="group w-12 h-12 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl flex items-center justify-center border border-white/30 transition-all duration-200 hover:scale-110">
                        <svg class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>

                    <div>
                        <h2 class="text-4xl font-bold text-white flex items-center gap-3">
                            <span data-translate>แก้ไขผู้ติดต่อ</span>
                        </h2>
                        <p class="text-emerald-100 mt-2 text-lg">{{ $contact->name }}</p>
                        <div class="flex gap-3 mt-3">
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-lg border border-white/30" data-translate>
                                ✏️ แก้ไขข้อมูล
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.accounting.contacts.update', $contact) }}" method="POST" class="max-w-5xl mx-auto">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-8 space-y-8">
            <!-- Contact Type -->
            <div>
                <label class="block text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <span data-translate>ประเภทผู้ติดต่อ</span> <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="group relative flex items-center p-6 border-2 rounded-2xl cursor-pointer transition-all duration-200 hover:shadow-lg border-blue-200 dark:border-blue-900 hover:border-blue-400 dark:hover:border-blue-600 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20">
                        <input type="checkbox" name="is_customer" value="1" {{ old('is_customer', $contact->is_customer) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 mr-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block font-bold text-gray-900 dark:text-white text-lg" data-translate>ลูกค้า</span>
                                <span class="text-xs text-gray-600 dark:text-gray-400" data-translate>Customer</span>
                            </div>
                        </div>
                    </label>

                    <label class="group relative flex items-center p-6 border-2 rounded-2xl cursor-pointer transition-all duration-200 hover:shadow-lg border-orange-200 dark:border-orange-900 hover:border-orange-400 dark:hover:border-orange-600 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20">
                        <input type="checkbox" name="is_vendor" value="1" {{ old('is_vendor', $contact->is_vendor) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 mr-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block font-bold text-gray-900 dark:text-white text-lg" data-translate>ผู้ขาย</span>
                                <span class="text-xs text-gray-600 dark:text-gray-400" data-translate>Vendor</span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="border-t-2 border-gray-200 dark:border-gray-700 pt-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>ข้อมูลพื้นฐาน</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span data-translate>ชื่อ-นามสกุล / ชื่อบริษัท</span> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $contact->name) }}" required
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                               placeholder="ชื่อผู้ติดต่อ" data-translate-placeholder>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Company Name -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span data-translate>ชื่อบริษัท (ถ้ามี)</span>
                        </label>
                        <input type="text" name="company_name" value="{{ old('company_name', $contact->company_name) }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                               placeholder="ชื่อบริษัท" data-translate-placeholder>
                        @error('company_name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span data-translate>อีเมล</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input type="email" name="email" value="{{ old('email', $contact->email) }}"
                                   class="w-full pl-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                                   placeholder="email@example.com">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span data-translate>โทรศัพท์</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}"
                                   class="w-full pl-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                                   placeholder="0812345678">
                        </div>
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tax ID -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span data-translate>เลขประจำตัวผู้เสียภาษี</span>
                        </label>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $contact->tax_id) }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                               placeholder="1234567890123">
                        @error('tax_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="border-t-2 border-gray-200 dark:border-gray-700 pt-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>ที่อยู่</span>
                </h3>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>ที่อยู่</label>
                        <textarea name="address" rows="3"
                                  class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                                  placeholder="บ้านเลขที่, ถนน" data-translate-placeholder>{{ old('address', $contact->address) }}</textarea>
                        @error('address')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>เขต/อำเภอ</label>
                            <input type="text" name="district" value="{{ old('district', $contact->district) }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>จังหวัด</label>
                            <input type="text" name="province" value="{{ old('province', $contact->province) }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>รหัสไปรษณีย์</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $contact->postal_code) }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes & Status -->
            <div class="border-t-2 border-gray-200 dark:border-gray-700 pt-8 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>หมายเหตุ</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 shadow-sm"
                              placeholder="ข้อมูลเพิ่มเติม" data-translate-placeholder>{{ old('notes', $contact->notes) }}</textarea>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border-2 border-green-200 dark:border-green-800">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $contact->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold text-gray-900 dark:text-white" data-translate>ใช้งาน</span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row justify-end gap-4 pt-6 border-t-2 border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                   class="group px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 font-semibold transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span data-translate>ยกเลิก</span>
                </a>
                <button type="submit"
                        class="group px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span data-translate>บันทึกการแก้ไข</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
