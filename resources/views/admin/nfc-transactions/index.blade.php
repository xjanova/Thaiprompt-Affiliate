{{--
    NFC Transactions Index - Admin Panel

    หน้าแสดงรายการธุรกรรม NFC ทั้งหมดในระบบ
    - แสดงสถิติธุรกรรม
    - ตัวกรองขั้นสูง (บัตร, เครื่องอ่าน, ประเภท, สถานะ, วันที่)
    - ค้นหาธุรกรรม
    - ส่งออก CSV
    - Pagination

    @version 3.0.0
    @since 2025-11-23
--}}

@extends('layouts.admin')

@section('title', 'ธุรกรรม NFC')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="nfcTransactions()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    📜 ธุรกรรม NFC
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    รายการธุรกรรมบัตร NFC Tap-to-Pay ทั้งหมดในระบบ
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.nfc.dashboard') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับ Dashboard
                </a>
                <button @click="exportCSV()"
                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                    <i class="fas fa-file-export mr-2"></i>
                    ส่งออก CSV
                </button>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        {{-- Total Transactions --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">ธุรกรรมทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($statistics['total_transactions']) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-receipt text-3xl"></i>
                </div>
            </div>
        </div>

        {{-- Today Transactions --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">ธุรกรรมวันนี้</p>
                    <p class="text-3xl font-bold">{{ number_format($statistics['today_transactions']) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-calendar-day text-3xl"></i>
                </div>
            </div>
        </div>

        {{-- Completed Transactions --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">สำเร็จ</p>
                    <p class="text-3xl font-bold">{{ number_format($statistics['completed_transactions']) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

        {{-- Failed Transactions --}}
        <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium mb-1">ล้มเหลว</p>
                    <p class="text-3xl font-bold">{{ number_format($statistics['failed_transactions']) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-3xl"></i>
                </div>
            </div>
        </div>

        {{-- Today Amount --}}
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 dark:from-teal-600 dark:to-teal-700 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-teal-100 text-sm font-medium mb-1">ยอดชำระวันนี้</p>
                    <p class="text-3xl font-bold">฿{{ number_format($statistics['total_amount_today'], 2) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-coins text-3xl"></i>
                </div>
            </div>
        </div>

        {{-- Month Amount --}}
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">ยอดชำระเดือนนี้</p>
                    <p class="text-3xl font-bold">฿{{ number_format($statistics['total_amount_this_month'], 2) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                <i class="fas fa-filter text-purple-500 mr-2"></i>
                ตัวกรอง
            </h2>
            <button @click="resetFilters()"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <i class="fas fa-redo mr-1"></i>
                รีเซ็ตตัวกรอง
            </button>
        </div>

        <form action="{{ route('admin.nfc-transactions.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Search --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search mr-1"></i>
                        ค้นหา
                    </label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Transaction ID, เลขบัตร, ผู้ใช้..."
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                </div>

                {{-- Type Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-tag mr-1"></i>
                        ประเภทธุรกรรม
                    </label>
                    <select name="type"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>ชำระเงิน</option>
                        <option value="topup" {{ request('type') === 'topup' ? 'selected' : '' }}>เติมเงิน</option>
                        <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>คืนเงิน</option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>โอนเงิน</option>
                        <option value="verification" {{ request('type') === 'verification' ? 'selected' : '' }}>ยืนยันบัตร</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        สถานะ
                    </label>
                    <select name="status"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>คืนเงินแล้ว</option>
                    </select>
                </div>

                {{-- Card Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-credit-card mr-1"></i>
                        บัตร NFC
                    </label>
                    <select name="card_id"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        @foreach($cards as $card)
                            <option value="{{ $card->id }}" {{ request('card_id') == $card->id ? 'selected' : '' }}>
                                {{ $card->card_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reader Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-tablet-alt mr-1"></i>
                        เครื่องอ่าน
                    </label>
                    <select name="reader_id"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        @foreach($readers as $reader)
                            <option value="{{ $reader->id }}" {{ request('reader_id') == $reader->id ? 'selected' : '' }}>
                                {{ $reader->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        วันที่เริ่มต้น
                    </label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-check mr-1"></i>
                        วันที่สิ้นสุด
                    </label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    พบธุรกรรม <strong class="text-purple-600 dark:text-purple-400">{{ number_format($transactions->total()) }}</strong> รายการ
                </p>
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($transactions->isEmpty())
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <i class="fas fa-receipt text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบธุรกรรม</h3>
                <p class="text-gray-500 dark:text-gray-400">ลองปรับเปลี่ยนตัวกรองหรือลองค้นหาใหม่</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Transaction ID
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                บัตร / ผู้ใช้
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                เครื่องอ่าน
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                ประเภท
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                จำนวนเงิน
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                ยอดคงเหลือ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                สถานที่
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                วันที่/เวลา
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-purple-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                {{-- Transaction ID --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-mono font-semibold text-gray-900 dark:text-white">
                                                {{ Str::limit($transaction->transaction_id, 16) }}
                                            </p>
                                            @if($transaction->receipt_number)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $transaction->receipt_number }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Card / User --}}
                                <td class="px-6 py-4">
                                    @if($transaction->nfcCard)
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                <i class="fas fa-credit-card text-purple-500 mr-1"></i>
                                                {{ $transaction->nfcCard->card_number }}
                                            </p>
                                            @if($transaction->user)
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    <i class="fas fa-user text-gray-400 mr-1"></i>
                                                    {{ $transaction->user->name }}
                                                </p>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Reader --}}
                                <td class="px-6 py-4">
                                    @if($transaction->nfcReader)
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <i class="fas fa-tablet-alt text-blue-500 mr-1"></i>
                                            {{ $transaction->nfcReader->name }}
                                        </p>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Type --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $typeConfig = [
                                            'payment' => ['icon' => 'fa-shopping-cart', 'color' => 'blue', 'label' => 'ชำระเงิน'],
                                            'topup' => ['icon' => 'fa-plus-circle', 'color' => 'green', 'label' => 'เติมเงิน'],
                                            'refund' => ['icon' => 'fa-undo', 'color' => 'yellow', 'label' => 'คืนเงิน'],
                                            'transfer' => ['icon' => 'fa-exchange-alt', 'color' => 'purple', 'label' => 'โอนเงิน'],
                                            'verification' => ['icon' => 'fa-check-circle', 'color' => 'teal', 'label' => 'ยืนยันบัตร'],
                                        ];
                                        $config = $typeConfig[$transaction->type] ?? ['icon' => 'fa-question', 'color' => 'gray', 'label' => $transaction->type];
                                    @endphp
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30 text-{{ $config['color'] }}-700 dark:text-{{ $config['color'] }}-400">
                                        <i class="fas {{ $config['icon'] }}"></i>
                                        {{ $config['label'] }}
                                    </span>
                                </td>

                                {{-- Amount --}}
                                <td class="px-6 py-4 text-right">
                                    <span class="text-base font-bold {{ in_array($transaction->type, ['payment']) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ in_array($transaction->type, ['payment']) ? '-' : '+' }}฿{{ number_format($transaction->amount, 2) }}
                                    </span>
                                </td>

                                {{-- Balance After --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm">
                                        <p class="text-gray-500 dark:text-gray-400 text-xs mb-1">ก่อน: ฿{{ number_format($transaction->balance_before, 2) }}</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">หลัง: ฿{{ number_format($transaction->balance_after, 2) }}</p>
                                    </div>
                                </td>

                                {{-- Location --}}
                                <td class="px-6 py-4">
                                    @if($transaction->location)
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                                            {{ Str::limit($transaction->location, 30) }}
                                        </p>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $statusConfig = [
                                            'pending' => ['color' => 'yellow', 'icon' => 'fa-clock', 'label' => 'รอดำเนินการ'],
                                            'processing' => ['color' => 'blue', 'icon' => 'fa-spinner', 'label' => 'กำลังดำเนินการ'],
                                            'completed' => ['color' => 'green', 'icon' => 'fa-check-circle', 'label' => 'สำเร็จ'],
                                            'failed' => ['color' => 'red', 'icon' => 'fa-times-circle', 'label' => 'ล้มเหลว'],
                                            'cancelled' => ['color' => 'gray', 'icon' => 'fa-ban', 'label' => 'ยกเลิก'],
                                            'refunded' => ['color' => 'purple', 'icon' => 'fa-undo-alt', 'label' => 'คืนเงินแล้ว'],
                                        ];
                                        $statusConf = $statusConfig[$transaction->status] ?? ['color' => 'gray', 'icon' => 'fa-question', 'label' => $transaction->status];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-{{ $statusConf['color'] }}-100 dark:bg-{{ $statusConf['color'] }}-900/30 text-{{ $statusConf['color'] }}-700 dark:text-{{ $statusConf['color'] }}-400">
                                        <i class="fas {{ $statusConf['icon'] }}"></i>
                                        {{ $statusConf['label'] }}
                                    </span>
                                </td>

                                {{-- Date/Time --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="text-gray-900 dark:text-white font-medium">
                                            <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                            {{ $transaction->created_at->format('d/m/Y') }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-clock text-gray-400 mr-1"></i>
                                            {{ $transaction->created_at->format('H:i:s') }}
                                        </p>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.nfc-transactions.show', $transaction) }}"
                                       class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    /**
     * NFC Transactions - Alpine.js Component
     *
     * จัดการฟังก์ชันต่างๆ ในหน้าธุรกรรม NFC
     */
    function nfcTransactions() {
        return {
            /**
             * รีเซ็ตตัวกรองทั้งหมด
             */
            resetFilters() {
                window.location.href = '{{ route('admin.nfc-transactions.index') }}';
            },

            /**
             * ส่งออกข้อมูลเป็น CSV
             */
            exportCSV() {
                // สร้าง URL พร้อมตัวกรองปัจจุบัน
                const params = new URLSearchParams(window.location.search);
                window.location.href = '{{ route('admin.nfc-transactions.export') }}?' + params.toString();
            }
        };
    }
</script>
@endpush
@endsection
