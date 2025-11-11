@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                        <svg class="w-10 h-10 mr-3 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        แก้ไขใบเสนอราคา
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">เลขที่: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</span></p>
                </div>
                <a href="{{ route('quotations.show', $quotation) }}"
                   class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    ยกเลิก
                </a>
            </div>
        </div>

        <!-- Validation Errors -->
        @if($errors->any())
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-6 rounded-lg shadow-lg">
            <div class="flex items-start">
                <svg class="w-6 h-6 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="font-bold mb-2">พบข้อผิดพลาด:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Edit Form -->
        <form action="{{ route('admin.quotations.update', $quotation) }}" method="POST" id="quotationForm" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Quotation Document -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <!-- Header with Gradient -->
                <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-700 dark:from-blue-700 dark:via-cyan-700 dark:to-blue-800 text-white p-8">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div class="flex-1">
                            @php
                                $logo = \App\Models\Setting::get('site_logo');
                                $siteName = \App\Models\Setting::get('site_name', config('app.name'));
                            @endphp
                            @if($logo)
                                <img src="{{ asset($logo) }}" alt="{{ $siteName }}" class="h-16 mb-4 brightness-0 invert">
                            @else
                                <h2 class="text-4xl font-bold mb-2">{{ $siteName }}</h2>
                            @endif
                            <p class="text-blue-100 dark:text-cyan-100 text-sm leading-relaxed">
                                @php
                                    $address = \App\Models\Setting::get('company_address', 'ที่อยู่บริษัท');
                                    $phone = \App\Models\Setting::get('contact_phone', '02-XXX-XXXX');
                                    $email = \App\Models\Setting::get('contact_email', 'info@company.com');
                                @endphp
                                {{ $address }}<br>
                                โทร: {{ $phone }} | อีเมล: {{ $email }}
                            </p>
                        </div>
                        <div class="text-left md:text-right">
                            <h2 class="text-3xl font-bold mb-2">ใบเสนอราคา</h2>
                            <p class="text-blue-100 dark:text-cyan-100 text-lg font-semibold">{{ $quotation->quotation_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status and Dates Section -->
                <div class="p-8 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        สถานะและวันที่
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                สถานะ <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                                <option value="draft" {{ $quotation->status === 'draft' ? 'selected' : '' }}>ร่าง</option>
                                <option value="pending" {{ $quotation->status === 'pending' ? 'selected' : '' }}>รอการตรวจสอบ</option>
                                <option value="reviewed" {{ $quotation->status === 'reviewed' ? 'selected' : '' }}>ตรวจสอบแล้ว</option>
                                <option value="sent" {{ $quotation->status === 'sent' ? 'selected' : '' }}>ส่งแล้ว</option>
                                <option value="accepted" {{ $quotation->status === 'accepted' ? 'selected' : '' }}>ลูกค้ายอมรับ</option>
                                <option value="rejected" {{ $quotation->status === 'rejected' ? 'selected' : '' }}>ลูกค้าปฏิเสธ</option>
                                <option value="expired" {{ $quotation->status === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                                <option value="converted" {{ $quotation->status === 'converted' ? 'selected' : '' }}>แปลงเป็นคำสั่งซื้อแล้ว</option>
                            </select>
                        </div>

                        <!-- Valid Until -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ใช้ได้ถึงวันที่
                            </label>
                            <input type="date" name="valid_until"
                                   value="{{ $quotation->valid_until ? $quotation->valid_until->format('Y-m-d') : '' }}"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        ข้อมูลลูกค้า
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Customer Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อลูกค้า <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', $quotation->customer_name) }}" required
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                        </div>

                        <!-- Customer Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                อีเมล <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', $quotation->customer_email) }}" required
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                        </div>

                        <!-- Customer Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                โทรศัพท์
                            </label>
                            <input type="text" name="customer_phone" value="{{ old('customer_phone', $quotation->customer_phone) }}"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                        </div>

                        <!-- Company Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อบริษัท
                            </label>
                            <input type="text" name="company_name" value="{{ old('company_name', $quotation->company_name) }}"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                        </div>

                        <!-- Tax ID -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                เลขประจำตัวผู้เสียภาษี
                            </label>
                            <input type="text" name="tax_id" value="{{ old('tax_id', $quotation->tax_id) }}"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                        </div>

                        <!-- Company Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ที่อยู่บริษัท
                            </label>
                            <textarea name="company_address" rows="3"
                                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">{{ old('company_address', $quotation->company_address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Items Section (Read-only display) -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        รายการสินค้า/บริการ
                    </h3>

                    <div class="overflow-x-auto rounded-lg border-2 border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-gray-900 dark:to-gray-800">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">รายการ</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จำนวน</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ราคา/หน่วย</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">รวม</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($quotation->items as $item)
                                <tr class="hover:bg-blue-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item->item_name }}</div>
                                        @if($item->item_description)
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $item->item_description }}</div>
                                        @endif
                                        @if($item->selectedOptions && $item->selectedOptions->count() > 0)
                                            <div class="mt-2 space-y-1">
                                                @foreach($item->selectedOptions as $option)
                                                    <div class="text-xs text-blue-600 dark:text-cyan-400 flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                        {{ $option->option_name }}
                                                        @if($option->price_modifier > 0)
                                                            <span class="ml-2 text-gray-600 dark:text-gray-400">(+{{ number_format($option->price_modifier, 2) }} ฿)</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ number_format($item->unit_price, 2) }} ฿
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ number_format($item->subtotal, 2) }} ฿
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 italic flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        หมายเหตุ: การแก้ไขรายการสินค้าต้องทำผ่านระบบจัดการสินค้า
                    </p>
                </div>

                <!-- Pricing Adjustments -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        การคำนวณราคา
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Discount Percentage -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ส่วนลด (%)
                            </label>
                            <div class="relative">
                                <input type="number" name="discount_percentage"
                                       value="{{ old('discount_percentage', $quotation->discount_percentage) }}"
                                       min="0" max="100" step="0.01"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 font-semibold">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Rate -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                อัตราภาษี (%)
                            </label>
                            <div class="relative">
                                <input type="number" name="tax_rate"
                                       value="{{ old('tax_rate', $quotation->tax_rate) }}"
                                       min="0" max="100" step="0.01"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 font-semibold">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Totals Display -->
                    <div class="mt-6 p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-gray-900 dark:to-gray-800 rounded-xl border-2 border-blue-200 dark:border-gray-700">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ยอดรวม:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->subtotal, 2) }} ฿</span>
                            </div>
                            @if($quotation->discount_amount > 0)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ส่วนลด:</span>
                                <span class="font-semibold text-red-600 dark:text-red-400">-{{ number_format($quotation->discount_amount, 2) }} ฿</span>
                            </div>
                            @endif
                            @if($quotation->tax_amount > 0)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ภาษี:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->tax_amount, 2) }} ฿</span>
                            </div>
                            @endif
                            <div class="pt-2 border-t-2 border-blue-200 dark:border-gray-700 flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900 dark:text-gray-100">ยอดรวมทั้งสิ้น:</span>
                                <span class="text-2xl font-bold text-blue-600 dark:text-cyan-400">{{ number_format($quotation->total_amount, 2) }} ฿</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        หมายเหตุ
                    </h3>

                    <div class="space-y-6">
                        <!-- Customer Notes -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                หมายเหตุสำหรับลูกค้า
                            </label>
                            <textarea name="notes" rows="4"
                                      placeholder="หมายเหตุที่ต้องการให้ลูกค้าเห็น..."
                                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 dark:focus:border-cyan-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-cyan-500/20 transition-all duration-200">{{ old('notes', $quotation->notes) }}</textarea>
                        </div>

                        <!-- Admin Notes -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                หมายเหตุภายใน (เฉพาะแอดมิน)
                            </label>
                            <textarea name="admin_notes" rows="4"
                                      placeholder="หมายเหตุสำหรับแอดมินเท่านั้น (ลูกค้าจะไม่เห็น)..."
                                      class="w-full px-4 py-3 rounded-xl border-2 border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 dark:focus:ring-purple-400/20 transition-all duration-200">{{ old('admin_notes', $quotation->admin_notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="p-8 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
                    <div class="flex flex-wrap gap-4 justify-end">
                        <a href="{{ route('quotations.show', $quotation) }}"
                           class="inline-flex items-center px-8 py-4 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            ยกเลิก
                        </a>

                        <button type="submit"
                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 dark:from-blue-500 dark:to-cyan-500 dark:hover:from-blue-600 dark:hover:to-cyan-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            บันทึกการแก้ไข
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form Enhancement Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-save draft functionality (optional)
        const form = document.getElementById('quotationForm');
        let changesMade = false;

        form.addEventListener('change', function() {
            changesMade = true;
        });

        // Warn before leaving if changes were made
        window.addEventListener('beforeunload', function(e) {
            if (changesMade) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Clear flag on form submit
        form.addEventListener('submit', function() {
            changesMade = false;
        });

        // Input validation and formatting
        const percentInputs = document.querySelectorAll('input[type="number"][max="100"]');
        percentInputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value > 100) this.value = 100;
                if (this.value < 0) this.value = 0;
            });
        });
    });
</script>

<style>
    /* Custom scrollbar for textarea */
    textarea::-webkit-scrollbar {
        width: 8px;
    }

    textarea::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    textarea::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.5);
        border-radius: 10px;
    }

    textarea::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.7);
    }

    /* Dark mode scrollbar */
    .dark textarea::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .dark textarea::-webkit-scrollbar-thumb {
        background: rgba(34, 211, 238, 0.5);
    }

    .dark textarea::-webkit-scrollbar-thumb:hover {
        background: rgba(34, 211, 238, 0.7);
    }

    /* Smooth transitions for all interactive elements */
    input, select, textarea, button, a {
        transition: all 0.2s ease-in-out;
    }

    /* Focus ring enhancement */
    input:focus, select:focus, textarea:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .dark input:focus, .dark select:focus, .dark textarea:focus {
        box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.1);
    }
</style>
@endsection
