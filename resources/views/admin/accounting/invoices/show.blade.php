@extends('layouts.admin')

@section('title', 'รายละเอียดใบแจ้งหนี้')

@section('content')
<div class="p-6 space-y-6" x-data="{ language: 'th' }">
    <!-- Modern Header with Language Switcher & Gradient -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Language Switcher -->
        <div class="absolute top-4 right-4 z-10">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 11.236 11.618 14z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="language === 'th' ? '🇹🇭 ไทย' : language === 'en' ? '🇬🇧 English' : language === 'zh' ? '🇨🇳 中文' : '🇯🇵 日本語'"></span>
                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-20" style="display: none;">
                    <button @click="language = 'th'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇹🇭</span>
                        <span class="text-gray-700 dark:text-gray-300">ภาษาไทย</span>
                    </button>
                    <button @click="language = 'en'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇬🇧</span>
                        <span class="text-gray-700 dark:text-gray-300">English</span>
                    </button>
                    <button @click="language = 'zh'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇨🇳</span>
                        <span class="text-gray-700 dark:text-gray-300">中文</span>
                    </button>
                    <button @click="language = 'ja'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇯🇵</span>
                        <span class="text-gray-700 dark:text-gray-300">日本語</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Header Content -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>รายละเอียดใบแจ้งหนี้</h1>
                        <p class="text-emerald-100 text-lg font-medium">{{ $invoice->document_number }}</p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                    <span class="relative flex h-3 w-3">
                        @if($invoice->status === 'paid')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        @elseif($invoice->status === 'overdue')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        @elseif($invoice->status === 'draft')
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400"></span>
                        @else
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                        @endif
                    </span>
                    <span class="text-white font-semibold">
                        @switch($invoice->status)
                            @case('paid') <span data-translate>ชำระแล้ว</span> @break
                            @case('draft') <span data-translate>ฉบับร่าง</span> @break
                            @case('overdue') <span data-translate>เกินกำหนด</span> @break
                            @case('partial') <span data-translate>ชำระบางส่วน</span> @break
                            @default <span data-translate>รอชำระ</span>
                        @endswitch
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                @if(auth()->user()->hasPermission('accounting.edit_invoices') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.invoices.edit', $invoice) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                    <span data-translate>แก้ไข</span>
                </a>
                @endif

                <a href="{{ route('admin.accounting.invoices.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Company and Contact Info Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Company Info Card -->
        @if($invoice->company)
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white" data-translate>ข้อมูลบริษัท</h3>
                </div>
            </div>
            <div class="p-6 space-y-3">
                <div class="text-gray-900 dark:text-white font-bold text-lg">{{ $invoice->company->name }}</div>
                @if($invoice->company->tax_id)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span><span data-translate>เลขที่ผู้เสียภาษี:</span> {{ $invoice->company->tax_id }}</span>
                </div>
                @endif
                @if($invoice->company->address)
                <div class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-emerald-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $invoice->company->address }}</span>
                </div>
                @endif
                @if($invoice->company->phone)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                    </svg>
                    <span>Tel: {{ $invoice->company->phone }}</span>
                </div>
                @endif
                @if($invoice->company->email)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    <span>{{ $invoice->company->email }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Customer Info Card -->
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
            <div class="bg-gradient-to-r from-teal-500 to-cyan-600 dark:from-teal-700 dark:to-cyan-800 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white" data-translate>ข้อมูลลูกค้า</h3>
                </div>
            </div>
            <div class="p-6 space-y-3">
                <div class="text-gray-900 dark:text-white font-bold text-lg">{{ $invoice->contact->name }}</div>
                @if($invoice->contact->tax_id)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span><span data-translate>เลขที่ผู้เสียภาษี:</span> {{ $invoice->contact->tax_id }}</span>
                </div>
                @endif
                @if($invoice->contact->address)
                <div class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-teal-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $invoice->contact->address }}</span>
                </div>
                @endif
                @if($invoice->contact->phone)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                    </svg>
                    <span>Tel: {{ $invoice->contact->phone }}</span>
                </div>
                @endif
                @if($invoice->contact->email)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    <span>{{ $invoice->contact->email }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Document Information Card -->
    <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white" data-translate>ข้อมูลเอกสาร</h3>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>เลขที่เอกสาร</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-semibold text-lg">
                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $invoice->document_number }}
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>ประเภท</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                        <svg class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        @switch($invoice->document_type)
                            @case('invoice') <span data-translate>ใบแจ้งหนี้</span> @break
                            @case('tax_invoice') <span data-translate>ใบกำกับภาษี</span> @break
                            @case('receipt') <span data-translate>ใบเสร็จรับเงิน</span> @break
                            @case('quotation') <span data-translate>ใบเสนอราคา</span> @break
                            @default {{ $invoice->document_type }}
                        @endswitch
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>วันที่</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                        <svg class="w-5 h-5 text-cyan-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        {{ $invoice->document_date->format('d/m/Y') }}
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>วันครบกำหนด</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                        <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
                    </div>
                </div>
            </div>

            @if($invoice->reference_number)
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2" data-translate>เลขที่อ้างอิง</label>
                <div class="text-gray-900 dark:text-white font-medium">{{ $invoice->reference_number }}</div>
            </div>
            @endif

            @if($invoice->note)
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2" data-translate>หมายเหตุ</label>
                <div class="text-gray-700 dark:text-gray-300 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">{{ $invoice->note }}</div>
            </div>
            @endif

            @if($invoice->terms)
            <div>
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2" data-translate>เงื่อนไขการชำระเงิน</label>
                <div class="text-gray-700 dark:text-gray-300 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">{{ $invoice->terms }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white" data-translate>รายการสินค้า/บริการ</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-750">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>รายการ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>จำนวน</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>หน่วย</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>ราคา/หน่วย</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>ส่วนลด</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">VAT</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>รวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoice->items as $index => $item)
                    <tr class="hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $item->description }}</div>
                            @if($item->product)
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $item->product->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-6 py-4 text-center text-gray-900 dark:text-white">{{ $item->unit }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">฿{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">
                            @if($item->discount_amount > 0)
                            <span class="text-red-600 dark:text-red-400">-฿{{ number_format($item->discount_amount, 2) }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">{{ number_format($item->tax_rate, 0) }}%</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">฿{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals Summary with Gradient -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 px-6 py-6">
            <div class="max-w-md ml-auto space-y-3">
                <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                    <span class="font-medium" data-translate>ยอดรวมก่อน VAT:</span>
                    <span class="font-semibold text-lg">฿{{ number_format($invoice->subtotal_amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                    <span class="font-medium">VAT:</span>
                    <span class="font-semibold text-lg">฿{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                @if($invoice->discount_amount > 0)
                <div class="flex justify-between items-center text-red-600 dark:text-red-400">
                    <span class="font-medium" data-translate>ส่วนลดท้ายบิล:</span>
                    <span class="font-semibold text-lg">-฿{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="h-px bg-gradient-to-r from-emerald-500 to-teal-600"></div>
                <div class="flex justify-between items-center bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-600 dark:to-teal-700 text-white px-6 py-4 rounded-xl shadow-lg">
                    <span class="font-bold text-xl" data-translate>ยอดรวมทั้งสิ้น:</span>
                    <span class="font-bold text-2xl">฿{{ number_format($invoice->total_amount, 2) }}</span>
                </div>
                @if($invoice->balance > 0)
                <div class="flex justify-between items-center bg-gradient-to-r from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-700 text-white px-6 py-4 rounded-xl shadow-lg">
                    <span class="font-bold text-lg" data-translate>คงเหลือ:</span>
                    <span class="font-bold text-xl">฿{{ number_format($invoice->balance, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Payment Records -->
    @if($invoice->payments && $invoice->payments->count() > 0)
    <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 dark:from-green-700 dark:to-emerald-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white" data-translate>ประวัติการชำระเงิน</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-750">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>วันที่</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>วิธีชำระ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>เลขที่อ้างอิง</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>จำนวนเงิน</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoice->payments as $payment)
                    <tr class="hover:bg-green-50 dark:hover:bg-green-900/10 transition-colors">
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            @switch($payment->payment_method)
                                @case('cash') <span data-translate>เงินสด</span> @break
                                @case('bank_transfer') <span data-translate>โอนเงิน</span> @break
                                @case('credit_card') <span data-translate>บัตรเครดิต</span> @break
                                @case('cheque') <span data-translate>เช็ค</span> @break
                                @default {{ $payment->payment_method }}
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $payment->reference ?? '-' }}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">฿{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $payment->note ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Action Buttons Footer -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div>
            @if($invoice->balance > 0 && (auth()->user()->hasPermission('accounting.create_payments') || auth()->user()->isSuperAdmin()))
            <button type="button" onclick="showPaymentModal()"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>บันทึกการชำระเงิน</span>
            </button>
            @endif
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="button"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                </svg>
                <span>Export PDF</span>
            </button>

            <button type="button"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-700 hover:from-purple-700 hover:to-pink-800 text-white rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                <span data-translate>ส่ง Email</span>
            </button>
        </div>
    </div>
</div>

<script>
function showPaymentModal() {
    // TODO: Implement payment modal
    alert('ฟีเจอร์บันทึกการชำระเงินกำลังพัฒนา');
}
</script>
@endsection
