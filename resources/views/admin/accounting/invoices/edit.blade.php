@extends('layouts.admin')

@section('title', 'แก้ไขใบแจ้งหนี้')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Modern Header with Accounting Theme -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <!-- Language Switcher Component -->
        <div class="absolute top-4 right-4 z-10">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open"
                        class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 flex items-center gap-2 shadow-lg border border-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                    <span x-text="{ th: 'ไทย 🇹🇭', en: 'English 🇬🇧', zh: '中文 🇨🇳', ja: '日本語 🇯🇵' }[language]"></span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
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
                     class="absolute right-0 mt-2 w-48 rounded-xl shadow-2xl bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 overflow-hidden"
                     style="display: none;">
                    <button @click="language = 'th'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">ไทย 🇹🇭</button>
                    <button @click="language = 'en'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">English 🇬🇧</button>
                    <button @click="language = 'zh'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">中文 🇨🇳</button>
                    <button @click="language = 'ja'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">日本語 🇯🇵</button>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.accounting.invoices.index') }}"
                   class="group p-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all duration-200 border border-white/20">
                    <svg class="w-6 h-6 text-white group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg flex items-center gap-3">
                            <span data-translate>แก้ไขใบแจ้งหนี้</span>
                            <span class="text-2xl">📝</span>
                        </h1>
                        <span class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl text-sm font-medium border border-white/20">
                            {{ $invoice->document_number }}
                        </span>
                    </div>
                    <p class="text-white/90 text-lg" data-translate>แก้ไขข้อมูลใบแจ้งหนี้/ใบกำกับภาษี</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="max-w-6xl mx-auto">
        <form action="{{ route('admin.accounting.invoices.update', $invoice) }}" method="POST" id="invoice-form"
              class="bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 overflow-hidden">
            @csrf
            @method('PUT')

            <!-- Document Information Section -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4 border-b-2 border-emerald-600 dark:border-emerald-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-white" data-translate>ข้อมูลเอกสาร</h2>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Company -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            บริษัท *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <select name="company_id"
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all appearance-none">
                                <option value="" data-translate>เลือกบริษัท</option>
                                @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ $invoice->company_id == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Document Type (Read Only) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            ประเภทเอกสาร
                        </label>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                @switch($invoice->document_type)
                                    @case('invoice') ใบแจ้งหนี้ @break
                                    @case('tax_invoice') ใบกำกับภาษี @break
                                    @case('receipt') ใบเสร็จรับเงิน @break
                                    @case('quotation') ใบเสนอราคา @break
                                    @default {{ $invoice->document_type }}
                                @endswitch
                            </span>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            ลูกค้า *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <select name="contact_id" required
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all appearance-none">
                                <option value="" data-translate>เลือกลูกค้า</option>
                                @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" {{ $invoice->contact_id == $contact->id ? 'selected' : '' }}>
                                    {{ $contact->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Document Date -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            วันที่เอกสาร *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="date" name="document_date" value="{{ old('document_date', $invoice->document_date->format('Y-m-d')) }}" required
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all">
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            วันที่ครบกำหนด
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all">
                        </div>
                    </div>

                    <!-- Reference Number -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            เลขที่อ้างอิง
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="text" name="reference_number" value="{{ old('reference_number', $invoice->reference_number) }}"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all"
                                   placeholder="PO-XXX หรืออื่นๆ" data-translate-placeholder>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Section -->
            <div class="bg-gradient-to-r from-teal-500 to-cyan-600 dark:from-teal-700 dark:to-cyan-800 px-6 py-4 border-y-2 border-teal-600 dark:border-teal-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-white" data-translate>รายการสินค้า/บริการ</h2>
                </div>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700">
                    <table class="w-full" id="line-items-table">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>รายการ</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>จำนวน</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>หน่วย</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ราคา/หน่วย</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ส่วนลด</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>VAT %</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>รวม</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="line-items" class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($invoice->items as $item)
                            <tr class="item-row hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors" data-item-id="{{ $item->id }}">
                                <td class="px-4 py-3">
                                    <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                    <input type="text" name="items[{{ $loop->index }}][description]" required value="{{ $item->description }}"
                                           class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[{{ $loop->index }}][quantity]" required min="0.01" step="0.01" value="{{ $item->quantity }}"
                                           class="w-24 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500"
                                           onchange="calculateRowTotal({{ $loop->index }})">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="items[{{ $loop->index }}][unit]" required value="{{ $item->unit }}"
                                           class="w-20 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-center focus:ring-2 focus:ring-emerald-500">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[{{ $loop->index }}][unit_price]" required min="0" step="0.01" value="{{ $item->unit_price }}"
                                           class="w-32 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500"
                                           onchange="calculateRowTotal({{ $loop->index }})">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[{{ $loop->index }}][discount_amount]" min="0" step="0.01" value="{{ $item->discount_amount }}"
                                           class="w-28 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500"
                                           onchange="calculateRowTotal({{ $loop->index }})">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[{{ $loop->index }}][tax_rate]" min="0" max="100" step="0.01" value="{{ $item->tax_rate }}"
                                           class="w-20 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500"
                                           onchange="calculateRowTotal({{ $loop->index }})">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="row-total font-bold text-gray-900 dark:text-white">฿{{ number_format($item->total_amount, 2) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" onclick="removeLineItem(this)"
                                            class="p-2 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" onclick="addLineItem()"
                        class="mt-4 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-700 hover:from-blue-700 hover:to-cyan-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>เพิ่มรายการ</span>
                </button>
            </div>

            <!-- Notes and Discount Section -->
            <div class="px-6 pb-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>ส่วนลดท้ายบิล</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 font-semibold">฿</span>
                            </div>
                            <input type="number" name="discount_amount" value="{{ old('discount_amount', $invoice->discount_amount) }}" min="0" step="0.01"
                                   class="w-full pl-8 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all"
                                   onchange="calculateTotal()">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>หมายเหตุ</label>
                    <textarea name="note" rows="3"
                              class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all">{{ old('note', $invoice->note) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" data-translate>เงื่อนไขการชำระเงิน</label>
                    <textarea name="terms" rows="2"
                              class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all">{{ old('terms', $invoice->terms) }}</textarea>
                </div>
            </div>

            <!-- Totals Section -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 px-6 py-6 border-t-2 border-gray-200 dark:border-gray-700">
                <div class="flex justify-end">
                    <div class="w-full max-w-md space-y-3">
                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-xl">
                            <span class="font-medium text-gray-700 dark:text-gray-300" data-translate>ยอดรวมก่อน VAT:</span>
                            <span id="subtotal" class="font-bold text-gray-900 dark:text-white">฿0.00</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-xl">
                            <span class="font-medium text-gray-700 dark:text-gray-300" data-translate>VAT:</span>
                            <span id="vat" class="font-bold text-blue-600 dark:text-blue-400">฿0.00</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-xl">
                            <span class="font-medium text-gray-700 dark:text-gray-300" data-translate>ส่วนลดท้ายบิล:</span>
                            <span id="discount" class="font-bold text-red-600 dark:text-red-400">฿0.00</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 rounded-xl shadow-lg">
                            <span class="font-bold text-lg text-white" data-translate>ยอดรวมทั้งสิ้น:</span>
                            <span id="total" class="font-bold text-2xl text-white">฿0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-6 flex justify-end gap-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.accounting.invoices.index') }}"
                   class="px-8 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold transition-all duration-200">
                    <span data-translate>ยกเลิก</span>
                </a>
                <button type="submit"
                        class="group px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                    </svg>
                    <span data-translate>อัพเดท</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = {{ count($invoice->items) }};
const products = @json($products);

function addLineItem() {
    const tbody = document.getElementById('line-items');
    const row = document.createElement('tr');
    row.className = 'item-row hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors';
    row.innerHTML = `
        <td class="px-4 py-3">
            <select name="items[${itemIndex}][product_id]" class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500" onchange="selectProduct(this, ${itemIndex})">
                <option value="">เลือกสินค้า/บริการ</option>
                ${products.map(p => `<option value="${p.id}" data-price="${p.price}" data-unit="${p.unit}">${p.name}</option>`).join('')}
            </select>
            <input type="text" name="items[${itemIndex}][description]" required placeholder="รายละเอียด" class="w-full mt-2 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][quantity]" required min="0.01" step="0.01" value="1" class="w-24 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-3">
            <input type="text" name="items[${itemIndex}][unit]" required value="ชิ้น" class="w-20 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-center focus:ring-2 focus:ring-emerald-500">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][unit_price]" required min="0" step="0.01" value="0" class="w-32 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][discount_amount]" min="0" step="0.01" value="0" class="w-28 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][tax_rate]" min="0" max="100" step="0.01" value="7" class="w-20 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right focus:ring-2 focus:ring-emerald-500" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-3 text-right">
            <span class="row-total font-bold text-gray-900 dark:text-white">฿0.00</span>
        </td>
        <td class="px-4 py-3 text-center">
            <button type="button" onclick="removeLineItem(this)" class="p-2 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    itemIndex++;
    calculateTotal();
}

function selectProduct(select, index) {
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const row = select.closest('tr');
        row.querySelector(`input[name="items[${index}][description]"]`).value = option.text;
        row.querySelector(`input[name="items[${index}][unit_price]"]`).value = option.dataset.price || 0;
        row.querySelector(`input[name="items[${index}][unit]"]`).value = option.dataset.unit || 'ชิ้น';
        calculateRowTotal(index);
    }
}

function calculateRowTotal(index) {
    const row = document.querySelector(`input[name="items[${index}][quantity]"]`).closest('tr');
    const quantity = parseFloat(row.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
    const unitPrice = parseFloat(row.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
    const discount = parseFloat(row.querySelector(`input[name="items[${index}][discount_amount]"]`).value) || 0;
    const taxRate = parseFloat(row.querySelector(`input[name="items[${index}][tax_rate]"]`).value) || 0;

    const subtotal = (quantity * unitPrice) - discount;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;

    row.querySelector('.row-total').textContent = `฿${total.toFixed(2)}`;
    calculateTotal();
}

function removeLineItem(button) {
    button.closest('tr').remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    let totalVat = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const inputs = row.querySelectorAll('input[type="number"]');
        let quantity, unitPrice, discount, taxRate;

        // Find correct inputs based on their names
        Array.from(row.querySelectorAll('input')).forEach(input => {
            if (input.name && input.name.includes('[quantity]')) quantity = parseFloat(input.value) || 0;
            if (input.name && input.name.includes('[unit_price]')) unitPrice = parseFloat(input.value) || 0;
            if (input.name && input.name.includes('[discount_amount]')) discount = parseFloat(input.value) || 0;
            if (input.name && input.name.includes('[tax_rate]')) taxRate = parseFloat(input.value) || 0;
        });

        const itemSubtotal = (quantity * unitPrice) - discount;
        const itemVat = itemSubtotal * (taxRate / 100);

        subtotal += itemSubtotal;
        totalVat += itemVat;
    });

    const billDiscount = parseFloat(document.querySelector('input[name="discount_amount"]').value) || 0;
    const total = subtotal + totalVat - billDiscount;

    document.getElementById('subtotal').textContent = `฿${subtotal.toFixed(2)}`;
    document.getElementById('vat').textContent = `฿${totalVat.toFixed(2)}`;
    document.getElementById('discount').textContent = `฿${billDiscount.toFixed(2)}`;
    document.getElementById('total').textContent = `฿${total.toFixed(2)}`;
}

// Calculate totals on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>
@endsection
