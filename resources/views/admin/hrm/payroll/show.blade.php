@extends('layouts.admin-v3')

@section('title', 'รายละเอียดเงินเดือน')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header Section --}}
        <div class="mb-8 animate-fade-in-down">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('admin.payroll.index') }}"
                           class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            รายละเอียดเงินเดือน
                        </h1>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 ml-9">
                        ข้อมูลการจ่ายเงินเดือนและรายละเอียดต่างๆ
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3">
                    @if($payroll->status === 'pending' || $payroll->status === 'draft')
                        <form action="{{ route('admin.payroll.approve', $payroll) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit"
                                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transform hover:scale-105 transition-all duration-200">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    อนุมัติ
                                </span>
                            </button>
                        </form>
                    @endif

                    @if($payroll->status === 'approved')
                        <button type="button"
                                onclick="openPaymentModal()"
                                class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transform hover:scale-105 transition-all duration-200">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                ทำการจ่าย
                            </span>
                        </button>
                    @endif

                    <a href="{{ route('admin.payroll.download-payslip', $payroll) }}"
                       class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transform hover:scale-105 transition-all duration-200">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            ดาวน์โหลดสลิป
                        </span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Status Badge and Pay Period --}}
        <div class="mb-6 flex flex-wrap items-center gap-4 animate-fade-in">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                        @if($payroll->status === 'paid') bg-gradient-to-r from-emerald-100 to-emerald-200 dark:from-emerald-900/30 dark:to-emerald-800/30 text-emerald-700 dark:text-emerald-400
                        @elseif($payroll->status === 'approved') bg-gradient-to-r from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 text-blue-700 dark:text-blue-400
                        @elseif($payroll->status === 'pending') bg-gradient-to-r from-amber-100 to-amber-200 dark:from-amber-900/30 dark:to-amber-800/30 text-amber-700 dark:text-amber-400
                        @else bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-800/30 dark:to-gray-700/30 text-gray-700 dark:text-gray-400
                        @endif
                        shadow-lg">
                <div class="w-2 h-2 rounded-full animate-pulse
                            @if($payroll->status === 'paid') bg-emerald-500
                            @elseif($payroll->status === 'approved') bg-blue-500
                            @elseif($payroll->status === 'pending') bg-amber-500
                            @else bg-gray-500
                            @endif">
                </div>
                <span class="font-semibold">
                    @if($payroll->status === 'paid') จ่ายแล้ว
                    @elseif($payroll->status === 'approved') อนุมัติแล้ว
                    @elseif($payroll->status === 'pending') รออนุมัติ
                    @else แบบร่าง
                    @endif
                </span>
            </div>

            <div class="px-4 py-2 bg-gradient-to-r from-purple-100 to-purple-200 dark:from-purple-900/30 dark:to-purple-800/30 text-purple-700 dark:text-purple-400 rounded-xl shadow-lg">
                <span class="flex items-center gap-2 font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    งวด {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->locale('th')->translatedFormat('F Y') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left Column - Employee Info --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Employee Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-300 animate-fade-in-left">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                            {{ substr($payroll->employee->full_name, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $payroll->employee->full_name }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                {{ $payroll->employee->employee_id }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">แผนก</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $payroll->employee->department->name ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">ตำแหน่ง</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $payroll->employee->position->title ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Timeline --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 animate-fade-in-left" style="animation-delay: 0.1s">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ไทม์ไลน์
                    </h4>

                    <div class="space-y-4">
                        {{-- Generated --}}
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                                @if($payroll->approved_at || $payroll->paid_at)
                                <div class="w-0.5 h-full bg-gradient-to-b from-green-400 to-gray-300 dark:to-gray-600 mt-2"></div>
                                @endif
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="font-semibold text-gray-900 dark:text-white">สร้างรายการ</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $payroll->created_at->locale('th')->translatedFormat('d F Y, H:i น.') }}
                                </p>
                                @if($payroll->generator)
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    โดย {{ $payroll->generator->name }}
                                </p>
                                @endif
                            </div>
                        </div>

                        {{-- Approved --}}
                        @if($payroll->approved_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                @if($payroll->paid_at)
                                <div class="w-0.5 h-full bg-gradient-to-b from-blue-400 to-gray-300 dark:to-gray-600 mt-2"></div>
                                @endif
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="font-semibold text-gray-900 dark:text-white">อนุมัติ</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($payroll->approved_at)->locale('th')->translatedFormat('d F Y, H:i น.') }}
                                </p>
                                @if($payroll->approver)
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    โดย {{ $payroll->approver->name }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Paid --}}
                        @if($payroll->paid_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white">จ่ายเงิน</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($payroll->paid_at)->locale('th')->translatedFormat('d F Y, H:i น.') }}
                                </p>
                                @if($payroll->payer)
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    โดย {{ $payroll->payer->name }}
                                </p>
                                @endif
                                @if($payroll->payment_reference)
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                                    อ้างอิง: {{ $payroll->payment_reference }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Right Column - Financial Details --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Summary Stats Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 animate-fade-in-right">

                    {{-- Gross Salary --}}
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="text-emerald-100 text-xs font-medium px-3 py-1 bg-white/10 rounded-lg backdrop-blur-sm">
                                รายได้รวม
                            </span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-3xl font-bold">
                                ฿{{ number_format($payroll->gross_salary, 2) }}
                            </p>
                            <p class="text-emerald-100 text-sm">
                                เงินเดือนก่อนหัก
                            </p>
                        </div>
                    </div>

                    {{-- Total Deductions --}}
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </div>
                            <span class="text-red-100 text-xs font-medium px-3 py-1 bg-white/10 rounded-lg backdrop-blur-sm">
                                รายการหัก
                            </span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-3xl font-bold">
                                ฿{{ number_format($payroll->total_deductions, 2) }}
                            </p>
                            <p class="text-red-100 text-sm">
                                ยอดหักทั้งหมด
                            </p>
                        </div>
                    </div>

                    {{-- Net Salary --}}
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 ring-4 ring-blue-400/50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <span class="text-blue-100 text-xs font-medium px-3 py-1 bg-white/10 rounded-lg backdrop-blur-sm">
                                รับสุทธิ
                            </span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-3xl font-bold">
                                ฿{{ number_format($payroll->net_salary, 2) }}
                            </p>
                            <p class="text-blue-100 text-sm">
                                เงินที่ได้รับจริง
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Earnings Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 animate-fade-in-right" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            รายได้
                        </h3>
                        <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                            ฿{{ number_format($payroll->gross_salary, 2) }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        {{-- Basic Salary --}}
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/10 dark:to-green-900/10 rounded-xl border border-emerald-200 dark:border-emerald-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">เงินเดือนพื้นฐาน</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Base Salary</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-emerald-700 dark:text-emerald-400">
                                ฿{{ number_format($payroll->basic_salary, 2) }}
                            </span>
                        </div>

                        {{-- Allowances --}}
                        @if($payroll->allowances > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">เบี้ยเลี้ยง</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Allowances</p>
                                </div>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">
                                +฿{{ number_format($payroll->allowances, 2) }}
                            </span>
                        </div>
                        @endif

                        {{-- Bonuses --}}
                        @if($payroll->bonuses > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">โบนัส</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Bonuses</p>
                                </div>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">
                                +฿{{ number_format($payroll->bonuses, 2) }}
                            </span>
                        </div>
                        @endif

                        {{-- Overtime --}}
                        @if($payroll->overtime_pay > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">ค่าล่วงเวลา</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Overtime</p>
                                </div>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">
                                +฿{{ number_format($payroll->overtime_pay, 2) }}
                            </span>
                        </div>
                        @endif

                        {{-- Commission --}}
                        @if($payroll->commission > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-teal-400 to-teal-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">ค่าคอมมิชชั่น</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Commission</p>
                                </div>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">
                                +฿{{ number_format($payroll->commission, 2) }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Deductions Breakdown --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 animate-fade-in-right" style="animation-delay: 0.2s">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </div>
                            รายการหัก
                        </h3>
                        <span class="text-2xl font-bold text-red-600 dark:text-red-400">
                            -฿{{ number_format($payroll->total_deductions, 2) }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        {{-- Tax --}}
                        @if($payroll->tax_deduction > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">ภาษีเงินได้</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Income Tax</p>
                                </div>
                            </div>
                            <span class="font-bold text-red-600 dark:text-red-400">
                                -฿{{ number_format($payroll->tax_deduction, 2) }}
                            </span>
                        </div>
                        @endif

                        {{-- Social Security --}}
                        @if($payroll->social_security > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-pink-400 to-pink-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">ประกันสังคม</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Social Security</p>
                                </div>
                            </div>
                            <span class="font-bold text-red-600 dark:text-red-400">
                                -฿{{ number_format($payroll->social_security, 2) }}
                            </span>
                        </div>
                        @endif

                        {{-- Provident Fund --}}
                        @if($payroll->provident_fund > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">กองทุนสำรองเลี้ยงชีพ</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Provident Fund</p>
                                </div>
                            </div>
                            <span class="font-bold text-red-600 dark:text-red-400">
                                -฿{{ number_format($payroll->provident_fund, 2) }}
                            </span>
                        </div>
                        @endif

                        {{-- Other Deductions --}}
                        @if($payroll->other_deductions > 0)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-gray-400 to-gray-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">รายการหักอื่นๆ</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Other Deductions</p>
                                </div>
                            </div>
                            <span class="font-bold text-red-600 dark:text-red-400">
                                -฿{{ number_format($payroll->other_deductions, 2) }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Visual Comparison Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 animate-fade-in-right" style="animation-delay: 0.3s">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        ภาพรวมรายได้และรายการหัก
                    </h3>

                    @php
                        $maxAmount = max($payroll->gross_salary, $payroll->total_deductions, $payroll->net_salary);
                        $grossWidth = ($payroll->gross_salary / $maxAmount) * 100;
                        $deductionsWidth = ($payroll->total_deductions / $maxAmount) * 100;
                        $netWidth = ($payroll->net_salary / $maxAmount) * 100;
                    @endphp

                    <div class="space-y-6">
                        {{-- Gross Salary Bar --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">รายได้รวม</span>
                                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                    ฿{{ number_format($payroll->gross_salary, 2) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-8 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-8 rounded-full flex items-center justify-end px-4 transition-all duration-1000 ease-out animate-slide-in-left"
                                     style="width: {{ $grossWidth }}%">
                                    <span class="text-white text-xs font-bold">{{ number_format($grossWidth, 1) }}%</span>
                                </div>
                            </div>
                        </div>

                        {{-- Deductions Bar --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">รายการหัก</span>
                                <span class="text-sm font-bold text-red-600 dark:text-red-400">
                                    ฿{{ number_format($payroll->total_deductions, 2) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-8 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-red-400 to-red-600 h-8 rounded-full flex items-center justify-end px-4 transition-all duration-1000 ease-out animate-slide-in-left"
                                     style="width: {{ $deductionsWidth }}%; animation-delay: 0.2s">
                                    <span class="text-white text-xs font-bold">{{ number_format($deductionsWidth, 1) }}%</span>
                                </div>
                            </div>
                        </div>

                        {{-- Net Salary Bar --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">รับสุทธิ</span>
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                    ฿{{ number_format($payroll->net_salary, 2) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-8 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-8 rounded-full flex items-center justify-end px-4 transition-all duration-1000 ease-out animate-slide-in-left"
                                     style="width: {{ $netWidth }}%; animation-delay: 0.4s">
                                    <span class="text-white text-xs font-bold">{{ number_format($netWidth, 1) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Summary Note --}}
                    <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-xl border-l-4 border-blue-500">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold text-blue-600 dark:text-blue-400">หมายเหตุ:</span>
                            จากรายได้รวม <span class="font-bold">฿{{ number_format($payroll->gross_salary, 2) }}</span>
                            หักรายการต่างๆ <span class="font-bold text-red-600">฿{{ number_format($payroll->total_deductions, 2) }}</span>
                            คงเหลือรับสุทธิ <span class="font-bold text-emerald-600">฿{{ number_format($payroll->net_salary, 2) }}</span>
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

{{-- Payment Modal --}}
<div id="paymentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="paymentModalContent">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    ทำการจ่าย
                </h3>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('admin.payroll.mark-paid', $payroll) }}" method="POST">
                @csrf

                <div class="mb-6">
                    <div class="p-4 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">จำนวนที่ต้องจ่าย</p>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                            ฿{{ number_format($payroll->net_salary, 2) }}
                        </p>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เลขอ้างอิงการชำระเงิน
                    </label>
                    <input type="text"
                           name="payment_reference"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-gray-900 dark:text-white transition-colors"
                           placeholder="เช่น TXN20240115001">
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            onclick="closePaymentModal()"
                            class="flex-1 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all">
                        ยืนยันการจ่าย
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fade-in-left {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fade-in-right {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slide-in-left {
    from {
        width: 0%;
    }
}

.animate-fade-in-down {
    animation: fade-in-down 0.6s ease-out;
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}

.animate-fade-in-left {
    animation: fade-in-left 0.6s ease-out;
}

.animate-fade-in-right {
    animation: fade-in-right 0.6s ease-out;
}

.animate-slide-in-left {
    animation: slide-in-left 1s ease-out forwards;
}
</style>

<script>
function openPaymentModal() {
    const modal = document.getElementById('paymentModal');
    const content = document.getElementById('paymentModalContent');

    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    const content = document.getElementById('paymentModalContent');

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Close modal on outside click
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePaymentModal();
    }
});
</script>

@endsection
