@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบบัญชี')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ตั้งค่าระบบบัญชี</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">กำหนดค่าและตั้งค่าระบบบัญชีของคุณ</p>
    </div>

    <form action="{{ route('admin.accounting.setup.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Company Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                <i class="fas fa-building text-blue-600 mr-2"></i>ข้อมูลบริษัท
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อบริษัท <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="บริษัท ABC จำกัด">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เลขประจำตัวผู้เสียภาษี <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="tax_id" value="{{ old('tax_id', $settings['tax_id'] ?? '') }}" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="1234567890123">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ที่อยู่</label>
                    <textarea name="company_address" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                              placeholder="ที่อยู่บริษัท">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โทรศัพท์</label>
                    <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="02-123-4567">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อีเมล</label>
                    <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? '') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="contact@company.com">
                </div>
            </div>
        </div>

        <!-- Accounting Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                <i class="fas fa-cog text-purple-600 mr-2"></i>การตั้งค่าบัญชี
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รอบบัญชี <span class="text-red-500">*</span>
                    </label>
                    <select name="fiscal_year_start" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="01-01" {{ ($settings['fiscal_year_start'] ?? '01-01') === '01-01' ? 'selected' : '' }}>1 มกราคม</option>
                        <option value="04-01" {{ ($settings['fiscal_year_start'] ?? '') === '04-01' ? 'selected' : '' }}>1 เมษายน</option>
                        <option value="07-01" {{ ($settings['fiscal_year_start'] ?? '') === '07-01' ? 'selected' : '' }}>1 กรกฎาคม</option>
                        <option value="10-01" {{ ($settings['fiscal_year_start'] ?? '') === '10-01' ? 'selected' : '' }}>1 ตุลาคม</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สกุลเงิน <span class="text-red-500">*</span>
                    </label>
                    <select name="currency" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="THB" {{ ($settings['currency'] ?? 'THB') === 'THB' ? 'selected' : '' }}>บาทไทย (THB)</option>
                        <option value="USD" {{ ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' }}>ดอลลาร์สหรัฐ (USD)</option>
                        <option value="EUR" {{ ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' }}>ยูโร (EUR)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปแบบเลขที่เอกสาร - ใบแจ้งหนี้
                    </label>
                    <input type="text" name="invoice_number_format" value="{{ old('invoice_number_format', $settings['invoice_number_format'] ?? 'INV-{YYYY}{MM}-{####}') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="INV-{YYYY}{MM}-{####}">
                    <p class="text-xs text-gray-500 mt-1">ใช้ {YYYY} = ปี, {MM} = เดือน, {####} = เลขที่</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปแบบเลขที่เอกสาร - รายจ่าย
                    </label>
                    <input type="text" name="expense_number_format" value="{{ old('expense_number_format', $settings['expense_number_format'] ?? 'EXP-{YYYY}{MM}-{####}') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="EXP-{YYYY}{MM}-{####}">
                </div>
            </div>
        </div>

        <!-- Tax Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                <i class="fas fa-percentage text-green-600 mr-2"></i>การตั้งค่าภาษี
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="enable_vat" value="1" {{ ($settings['enable_vat'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งานภาษีมูลค่าเพิ่ม (VAT)</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        อัตราภาษีมูลค่าเพิ่ม (%)
                    </label>
                    <input type="number" name="vat_rate" value="{{ old('vat_rate', $settings['vat_rate'] ?? 7) }}" step="0.01" min="0"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="enable_withholding_tax" value="1" {{ ($settings['enable_withholding_tax'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งานภาษีหัก ณ ที่จ่าย</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        อัตราภาษีหัก ณ ที่จ่าย (%)
                    </label>
                    <input type="number" name="withholding_tax_rate" value="{{ old('withholding_tax_rate', $settings['withholding_tax_rate'] ?? 3) }}" step="0.01" min="0"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Payment Terms -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                <i class="fas fa-calendar-alt text-orange-600 mr-2"></i>เงื่อนไขการชำระเงิน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        กำหนดเครดิตมาตรฐาน (วัน)
                    </label>
                    <input type="number" name="default_payment_terms" value="{{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) }}" min="0"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        แจ้งเตือนก่อนครบกำหนด (วัน)
                    </label>
                    <input type="number" name="payment_reminder_days" value="{{ old('payment_reminder_days', $settings['payment_reminder_days'] ?? 3) }}" min="0"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                <i class="fas fa-envelope text-red-600 mr-2"></i>การตั้งค่าอีเมล
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="send_invoice_email" value="1" {{ ($settings['send_invoice_email'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ส่งอีเมลใบแจ้งหนี้อัตโนมัติ</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="send_payment_reminder" value="1" {{ ($settings['send_payment_reminder'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ส่งอีเมลแจ้งเตือนการชำระเงิน</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="send_receipt_email" value="1" {{ ($settings['send_receipt_email'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ส่งอีเมลใบเสร็จรับเงิน</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
