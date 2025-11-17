@extends('layouts.admin-v3')

@section('title', 'รายละเอียดรายจ่าย')

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
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>รายละเอียดรายจ่าย</h1>
                        <p class="text-emerald-100 text-lg font-medium">{{ $expense->document_number }}</p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                    <span class="relative flex h-3 w-3">
                        @if($expense->status === 'paid')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        @elseif($expense->status === 'draft')
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400"></span>
                        @else
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                        @endif
                    </span>
                    <span class="text-white font-semibold">
                        @switch($expense->status)
                            @case('paid') <span data-translate>ชำระแล้ว</span> @break
                            @case('draft') <span data-translate>ฉบับร่าง</span> @break
                            @default <span data-translate>รอชำระ</span>
                        @endswitch
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                @if(auth()->user()->hasPermission('accounting.edit_expenses') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.expenses.edit', $expense) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                    <span data-translate>แก้ไข</span>
                </a>
                @endif

                <a href="{{ route('admin.accounting.expenses.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Document Information Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white" data-translate>ข้อมูลเอกสาร</h2>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>เลขที่เอกสาร</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-semibold text-lg">
                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $expense->document_number }}
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>วันที่</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                        <svg class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        {{ $expense->document_date->format('d/m/Y') }}
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>ผู้ขาย</label>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white font-medium">
                        <svg class="w-5 h-5 text-cyan-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        {{ $expense->contact->name ?? '-' }}
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400" data-translate>สถานะ</label>
                    <span class="inline-flex items-center px-3 py-1 text-xs leading-5 font-semibold rounded-full
                        @if($expense->status === 'paid') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                        @elseif($expense->status === 'draft') bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                        @else bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 @endif">
                        {{ $expense->status }}
                    </span>
                </div>
            </div>

            @if($expense->notes)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2" data-translate>หมายเหตุ</label>
                <div class="text-gray-700 dark:text-gray-300 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">{{ $expense->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    @if($expense->items && $expense->items->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white" data-translate>รายการสินค้า/บริการ</h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-750">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>รายการ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>จำนวน</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>ราคา</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>รวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($expense->items as $item)
                    <tr class="hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors">
                        <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $item->description }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">฿{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white font-bold">฿{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Total Summary -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 px-6 py-6">
            <div class="max-w-md ml-auto">
                <div class="flex justify-between items-center bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-600 dark:to-teal-700 text-white px-6 py-4 rounded-xl shadow-lg">
                    <span class="font-bold text-xl" data-translate>ยอดรวมทั้งสิ้น:</span>
                    <span class="font-bold text-2xl">฿{{ number_format($expense->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
