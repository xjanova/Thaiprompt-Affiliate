@extends('layouts.admin')

@section('title', 'ระบบบัญชี - Dashboard')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📊 ระบบบัญชี</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">ระบบบัญชีครบวงจร เชื่อมต่อกับ FlowAccount</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ใบแจ้งหนี้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_invoices']) }}</p>
                    <p class="text-sm text-orange-600 mt-1">รอชำระ: {{ $stats['pending_invoices'] }}</p>
                </div>
                <div class="text-4xl">📄</div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">รายจ่ายทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_expenses']) }}</p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>

        <!-- Total Contacts -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ผู้ติดต่อ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_contacts']) }}</p>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <!-- Total Products -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">สินค้า/บริการ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_products']) }}</p>
                </div>
                <div class="text-4xl">📦</div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">รายรับเดือนนี้</p>
            <p class="text-3xl font-bold mt-2">฿{{ number_format($financial['monthly_revenue'], 2) }}</p>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">รายจ่ายเดือนนี้</p>
            <p class="text-3xl font-bold mt-2">฿{{ number_format($financial['monthly_expenses'], 2) }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">กำไรเดือนนี้</p>
            <p class="text-3xl font-bold mt-2">฿{{ number_format($financial['monthly_profit'], 2) }}</p>
        </div>
    </div>

    <!-- Outstanding -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ลูกหนี้คงค้าง</h3>
            <p class="text-2xl font-bold text-orange-600">฿{{ number_format($financial['outstanding_receivables'], 2) }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เจ้าหนี้คงค้าง</h3>
            <p class="text-2xl font-bold text-red-600">฿{{ number_format($financial['outstanding_payables'], 2) }}</p>
        </div>
    </div>

    <!-- Recent Invoices & Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ใบแจ้งหนี้ล่าสุด</h3>
            @if($recentInvoices->count() > 0)
                <div class="space-y-3">
                    @foreach($recentInvoices as $invoice)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->document_number }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->contact->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($invoice->total_amount, 2) }}</p>
                            <span class="text-xs px-2 py-1 rounded-full
                                @if($invoice->status === 'paid') bg-green-100 text-green-800
                                @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ $invoice->status }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">ยังไม่มีใบแจ้งหนี้</p>
            @endif
            <a href="{{ route('admin.accounting.invoices.index') }}" class="block mt-4 text-center text-green-600 hover:text-green-700 font-medium">
                ดูทั้งหมด →
            </a>
        </div>

        <!-- Recent Expenses -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รายจ่ายล่าสุด</h3>
            @if($recentExpenses->count() > 0)
                <div class="space-y-3">
                    @foreach($recentExpenses as $expense)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $expense->document_number }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $expense->contact->name ?? 'ไม่ระบุ' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($expense->total_amount, 2) }}</p>
                            <span class="text-xs px-2 py-1 rounded-full
                                @if($expense->status === 'paid') bg-green-100 text-green-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ $expense->status }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">ยังไม่มีรายจ่าย</p>
            @endif
            <a href="{{ route('admin.accounting.expenses.index') }}" class="block mt-4 text-center text-green-600 hover:text-green-700 font-medium">
                ดูทั้งหมด →
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.accounting.invoices.create') }}" class="flex flex-col items-center justify-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                <i class="fas fa-plus-circle text-2xl text-green-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white">สร้างใบแจ้งหนี้</span>
            </a>

            <a href="{{ route('admin.accounting.expenses.create') }}" class="flex flex-col items-center justify-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                <i class="fas fa-plus-circle text-2xl text-red-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white">บันทึกรายจ่าย</span>
            </a>

            <a href="{{ route('admin.accounting.contacts.create') }}" class="flex flex-col items-center justify-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <i class="fas fa-user-plus text-2xl text-blue-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white">เพิ่มผู้ติดต่อ</span>
            </a>

            <a href="{{ route('admin.accounting.reports.index') }}" class="flex flex-col items-center justify-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <i class="fas fa-chart-bar text-2xl text-purple-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white">รายงาน</span>
            </a>
        </div>
    </div>
</div>
@endsection
