@extends('layouts.admin')

@section('title', 'รายละเอียดรายจ่าย')

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💰 รายละเอียดรายจ่าย</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $expense->document_number }}</p>
            </div>
            <div class="flex gap-2">
                @if(auth()->user()->hasPermission('accounting.edit_expenses') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.expenses.edit', $expense) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-edit mr-2"></i>แก้ไข
                </a>
                @endif
                <a href="{{ route('admin.accounting.expenses.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    กลับ
                </a>
            </div>
        </div>

        <!-- Document Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">เลขที่เอกสาร</label>
                    <div class="text-gray-900 dark:text-white font-semibold">{{ $expense->document_number }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">วันที่</label>
                    <div class="text-gray-900 dark:text-white">{{ $expense->document_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ผู้ขาย</label>
                    <div class="text-gray-900 dark:text-white">{{ $expense->contact->name ?? '-' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                        @if($expense->status === 'paid') bg-green-100 text-green-800
                        @elseif($expense->status === 'draft') bg-gray-100 text-gray-800
                        @else bg-blue-100 text-blue-800 @endif">
                        {{ $expense->status }}
                    </span>
                </div>
            </div>

            @if($expense->notes)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">หมายเหตุ</label>
                <div class="text-gray-900 dark:text-white">{{ $expense->notes }}</div>
            </div>
            @endif
        </div>

        <!-- Line Items -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายการ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราคา</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($expense->items as $item)
                    <tr>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $item->description }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">฿{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white font-semibold">฿{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <td colspan="3" class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">ยอดรวมทั้งสิ้น</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white text-lg">฿{{ number_format($expense->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
