@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
    <div class="max-w-5xl mx-auto">
        <!-- Success Message -->
        @if(session('success'))
        <div class="mb-6 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-lg animate-fade-in">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="mb-6 flex flex-wrap gap-3 print:hidden">
            <button onclick="window.print()"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 dark:from-blue-500 dark:to-cyan-500 dark:hover:from-blue-600 dark:hover:to-cyan-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                พิมพ์ใบเสนอราคา
            </button>

            @if(Auth::user()->is_admin)
                <a href="{{ route('admin.quotations.edit', $quotation) }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    แก้ไขใบเสนอราคา
                </a>
            @endif

            @if($quotation->canBeAccepted())
                <form action="{{ route('quotations.accept', $quotation) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('คุณต้องการยอมรับใบเสนอราคานี้หรือไม่?')"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 dark:from-green-500 dark:to-emerald-500 dark:hover:from-green-600 dark:hover:to-emerald-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        ยอมรับใบเสนอราคา
                    </button>
                </form>

                <form action="{{ route('quotations.reject', $quotation) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('คุณต้องการปฏิเสธใบเสนอราคานี้หรือไม่?')"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 dark:from-red-500 dark:to-rose-500 dark:hover:from-red-600 dark:hover:to-rose-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        ปฏิเสธใบเสนอราคา
                    </button>
                </form>
            @endif

            @if($quotation->status === 'accepted' && !$quotation->order_id)
                <form action="{{ route('quotations.convert', $quotation) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 dark:from-indigo-500 dark:to-blue-500 dark:hover:from-indigo-600 dark:hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        ดำเนินการสั่งซื้อ
                    </button>
                </form>
            @endif

            <a href="{{ route('quotations.index') }}"
               class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปรายการ
            </a>
        </div>

        <!-- Quotation Document -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <!-- Header with Gradient -->
            <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-700 dark:from-blue-700 dark:via-cyan-700 dark:to-blue-800 text-white p-8 print:bg-blue-600">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex-1">
                        @php
                            $logo = \App\Models\Setting::get('site_logo');
                            $siteName = \App\Models\Setting::get('site_name', config('app.name'));
                        @endphp
                        @if($logo)
                            <img src="{{ asset($logo) }}" alt="{{ $siteName }}" class="h-16 mb-4 brightness-0 invert">
                        @else
                            <h1 class="text-4xl font-bold mb-2">{{ $siteName }}</h1>
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

            <!-- Status Badge -->
            <div class="px-8 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 print:hidden">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold shadow-md
                            @if($quotation->status === 'draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @elseif($quotation->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                            @elseif($quotation->status === 'reviewed') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                            @elseif($quotation->status === 'sent') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                            @elseif($quotation->status === 'accepted') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                            @elseif($quotation->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                            @elseif($quotation->status === 'converted') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endif">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="4"/>
                            </svg>
                            {{ $quotation->status_label }}
                        </span>
                    </div>
                    @if($quotation->valid_until)
                    <div class="text-sm">
                        <span class="text-gray-600 dark:text-gray-400">ใช้ได้ถึง:</span>
                        <span class="font-semibold ml-2 {{ $quotation->isExpired() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                            {{ $quotation->valid_until->format('d/m/Y') }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Document Info -->
            <div class="p-8 grid md:grid-cols-2 gap-8 border-b border-gray-200 dark:border-gray-700">
                <!-- Customer Info -->
                <div class="space-y-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        ข้อมูลลูกค้า
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">ชื่อ:</span> {{ $quotation->customer_name }}</p>
                        <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">อีเมล:</span> {{ $quotation->customer_email }}</p>
                        @if($quotation->customer_phone)
                            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">โทรศัพท์:</span> {{ $quotation->customer_phone }}</p>
                        @endif
                        @if($quotation->company_name)
                            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">บริษัท:</span> {{ $quotation->company_name }}</p>
                        @endif
                        @if($quotation->tax_id)
                            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">เลขประจำตัวผู้เสียภาษี:</span> {{ $quotation->tax_id }}</p>
                        @endif
                        @if($quotation->company_address)
                            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">ที่อยู่:</span> {{ $quotation->company_address }}</p>
                        @endif
                    </div>
                </div>

                <!-- Quotation Info -->
                <div class="space-y-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        รายละเอียดเอกสาร
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">เลขที่:</span> {{ $quotation->quotation_number }}</p>
                        <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">วันที่สร้าง:</span> {{ $quotation->created_at->format('d/m/Y H:i') }}</p>
                        @if($quotation->valid_until)
                            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">ใช้ได้ถึง:</span> {{ $quotation->valid_until->format('d/m/Y') }}</p>
                        @endif
                        @if($quotation->sent_at)
                            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">วันที่ส่ง:</span> {{ $quotation->sent_at->format('d/m/Y H:i') }}</p>
                        @endif
                        @if($quotation->accepted_at)
                            <p class="text-green-600 dark:text-green-400"><span class="font-semibold">วันที่ยอมรับ:</span> {{ $quotation->accepted_at->format('d/m/Y H:i') }}</p>
                        @endif
                        @if($quotation->rejected_at)
                            <p class="text-red-600 dark:text-red-400"><span class="font-semibold">วันที่ปฏิเสธ:</span> {{ $quotation->rejected_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="p-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    รายการสินค้า/บริการ
                </h3>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
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
            </div>

            <!-- Totals Section -->
            <div class="px-8 pb-8">
                <div class="flex justify-end">
                    <div class="w-full md:w-1/2 lg:w-1/3 space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">ยอดรวม:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->subtotal, 2) }} ฿</span>
                        </div>

                        @if($quotation->discount_amount > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                ส่วนลด @if($quotation->discount_percentage > 0)({{ number_format($quotation->discount_percentage, 2) }}%)@endif:
                            </span>
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400">-{{ number_format($quotation->discount_amount, 2) }} ฿</span>
                        </div>
                        @endif

                        @if($quotation->tax_amount > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                ภาษี @if($quotation->tax_rate > 0)({{ number_format($quotation->tax_rate, 2) }}%)@endif:
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->tax_amount, 2) }} ฿</span>
                        </div>
                        @endif

                        <div class="flex justify-between items-center py-4 bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-700 dark:to-cyan-700 text-white rounded-xl px-6 shadow-lg">
                            <span class="text-lg font-bold">ยอดรวมทั้งสิ้น:</span>
                            <span class="text-2xl font-bold">{{ number_format($quotation->total_amount, 2) }} ฿</span>
                        </div>

                        @if($quotation->setup_total > 0 || $quotation->monthly_total > 0)
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-gray-900/50 rounded-lg space-y-2">
                            @if($quotation->setup_total > 0)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ค่าติดตั้งเริ่มต้น:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->setup_total, 2) }} ฿</span>
                            </div>
                            @endif
                            @if($quotation->monthly_total > 0)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ค่าบริการรายเดือน:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->monthly_total, 2) }} ฿/เดือน</span>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($quotation->notes || $quotation->admin_notes)
            <div class="px-8 pb-8 space-y-4">
                @if($quotation->notes)
                <div class="bg-blue-50 dark:bg-gray-900/50 rounded-xl p-6 border border-blue-200 dark:border-gray-700">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        หมายเหตุ:
                    </h4>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $quotation->notes }}</p>
                </div>
                @endif

                @if($quotation->admin_notes && Auth::user()->is_admin)
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        หมายเหตุสำหรับแอดมิน:
                    </h4>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $quotation->admin_notes }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Footer -->
            <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 border-t border-gray-200 dark:border-gray-700 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ขอบคุณที่ให้ความสนใจในบริการของเรา</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                    เอกสารนี้สร้างโดยระบบอัตโนมัติ |
                    <span class="font-semibold">{{ \App\Models\Setting::get('site_name', config('app.name')) }}</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
    @media print {
        body {
            background: white !important;
            padding: 0 !important;
        }

        .print\\:hidden {
            display: none !important;
        }

        .bg-gradient-to-r,
        .bg-gradient-to-br {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .shadow-2xl,
        .shadow-xl,
        .shadow-lg {
            box-shadow: none !important;
        }

        .rounded-2xl,
        .rounded-xl {
            border-radius: 0.5rem !important;
        }

        @page {
            margin: 1cm;
        }
    }

    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
</style>
@endsection
