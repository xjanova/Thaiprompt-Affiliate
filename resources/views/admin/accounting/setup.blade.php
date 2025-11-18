@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบบัญชี')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open"
                        class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">
                        🇹🇭 ไทย
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">
                        🇬🇧 English
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">
                        🇨🇳 中文
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">
                        🇯🇵 日本語
                    </a>
                </div>
            </div>
        </div>

        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-pink-300/20 rounded-full blur-3xl"></div>

            <div class="relative">
                <div class="flex items-center mb-3">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>
                            ตั้งค่าระบบบัญชี
                        </h1>
                        <p class="text-purple-100" data-translate>
                            กำหนดค่าและตั้งค่าระบบบัญชีของคุณ
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form action="{{ route('admin.accounting.setup.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Company Information Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white" data-translate>
                            ข้อมูลบริษัท
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm">
                                <span data-translate>ชื่อบริษัท</span>
                                <span class="text-yellow-300 ml-1">*</span>
                            </label>
                            <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" required
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                   placeholder="บริษัท ABC จำกัด"
                                   data-translate>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm">
                                <span data-translate>เลขประจำตัวผู้เสียภาษี</span>
                                <span class="text-yellow-300 ml-1">*</span>
                            </label>
                            <input type="text" name="tax_id" value="{{ old('tax_id', $settings['tax_id'] ?? '') }}" required
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                   placeholder="1234567890123">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>ที่อยู่</label>
                            <textarea name="company_address" rows="3"
                                      class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                      placeholder="ที่อยู่บริษัท"
                                      data-translate>{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>โทรศัพท์</label>
                            <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                   placeholder="02-123-4567">
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>อีเมล</label>
                            <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? '') }}"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                   placeholder="contact@company.com">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounting Settings Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 via-pink-500 to-rose-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white" data-translate>
                            การตั้งค่าบัญชี
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm">
                                <span data-translate>รอบบัญชี</span>
                                <span class="text-yellow-300 ml-1">*</span>
                            </label>
                            <select name="fiscal_year_start" required
                                    class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                                <option value="01-01" {{ ($settings['fiscal_year_start'] ?? '01-01') === '01-01' ? 'selected' : '' }}>1 มกราคม</option>
                                <option value="04-01" {{ ($settings['fiscal_year_start'] ?? '') === '04-01' ? 'selected' : '' }}>1 เมษายน</option>
                                <option value="07-01" {{ ($settings['fiscal_year_start'] ?? '') === '07-01' ? 'selected' : '' }}>1 กรกฎาคม</option>
                                <option value="10-01" {{ ($settings['fiscal_year_start'] ?? '') === '10-01' ? 'selected' : '' }}>1 ตุลาคม</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm">
                                <span data-translate>สกุลเงิน</span>
                                <span class="text-yellow-300 ml-1">*</span>
                            </label>
                            <select name="currency" required
                                    class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                                <option value="THB" {{ ($settings['currency'] ?? 'THB') === 'THB' ? 'selected' : '' }}>บาทไทย (THB)</option>
                                <option value="USD" {{ ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' }}>ดอลลาร์สหรัฐ (USD)</option>
                                <option value="EUR" {{ ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' }}>ยูโร (EUR)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                รูปแบบเลขที่เอกสาร - ใบแจ้งหนี้
                            </label>
                            <input type="text" name="invoice_number_format" value="{{ old('invoice_number_format', $settings['invoice_number_format'] ?? 'INV-{YYYY}{MM}-{####}') }}"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                   placeholder="INV-{YYYY}{MM}-{####}">
                            <p class="text-xs text-white/80 mt-2" data-translate>ใช้ {YYYY} = ปี, {MM} = เดือน, {####} = เลขที่</p>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                รูปแบบเลขที่เอกสาร - รายจ่าย
                            </label>
                            <input type="text" name="expense_number_format" value="{{ old('expense_number_format', $settings['expense_number_format'] ?? 'EXP-{YYYY}{MM}-{####}') }}"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all"
                                   placeholder="EXP-{YYYY}{MM}-{####}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Settings Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-green-500 via-emerald-500 to-teal-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white" data-translate>
                            การตั้งค่าภาษี
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_vat" value="1" {{ ($settings['enable_vat'] ?? true) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-2 border-white/50 text-green-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>เปิดใช้งานภาษีมูลค่าเพิ่ม (VAT)</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                อัตราภาษีมูลค่าเพิ่ม (%)
                            </label>
                            <input type="number" name="vat_rate" value="{{ old('vat_rate', $settings['vat_rate'] ?? 7) }}" step="0.01" min="0"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_withholding_tax" value="1" {{ ($settings['enable_withholding_tax'] ?? true) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-2 border-white/50 text-green-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>เปิดใช้งานภาษีหัก ณ ที่จ่าย</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                อัตราภาษีหัก ณ ที่จ่าย (%)
                            </label>
                            <input type="number" name="withholding_tax_rate" value="{{ old('withholding_tax_rate', $settings['withholding_tax_rate'] ?? 3) }}" step="0.01" min="0"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Terms Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-yellow-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white" data-translate>
                            เงื่อนไขการชำระเงิน
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                กำหนดเครดิตมาตรฐาน (วัน)
                            </label>
                            <input type="number" name="default_payment_terms" value="{{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) }}" min="0"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                แจ้งเตือนก่อนครบกำหนด (วัน)
                            </label>
                            <input type="number" name="payment_reminder_days" value="{{ old('payment_reminder_days', $settings['payment_reminder_days'] ?? 3) }}" min="0"
                                   class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Settings Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-red-500 via-rose-500 to-pink-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white" data-translate>
                            การตั้งค่าอีเมล
                        </h3>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="send_invoice_email" value="1" {{ ($settings['send_invoice_email'] ?? true) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-2 border-white/50 text-red-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>ส่งอีเมลใบแจ้งหนี้อัตโนมัติ</span>
                            </label>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="send_payment_reminder" value="1" {{ ($settings['send_payment_reminder'] ?? true) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-2 border-white/50 text-red-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>ส่งอีเมลแจ้งเตือนการชำระเงิน</span>
                            </label>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="send_receipt_email" value="1" {{ ($settings['send_receipt_email'] ?? true) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-2 border-white/50 text-red-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>ส่งอีเมลใบเสร็จรับเงิน</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit"
                        class="relative overflow-hidden group px-10 py-4 bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-3xl transition-all transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-700 via-emerald-700 to-teal-700 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span data-translate>บันทึกการตั้งค่า</span>
                    </div>
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Alpine.js -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
