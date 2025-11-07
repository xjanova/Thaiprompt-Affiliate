@extends('layouts.admin')

@section('title', 'รายจ่าย')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💰 รายจ่าย</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">จัดการรายจ่ายและค่าใช้จ่าย</p>
        </div>
        @if(auth()->user()->hasPermission('accounting.create_expenses') || auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.accounting.expenses.create') }}"
           class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>สร้างรายจ่าย
        </a>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">แบบร่าง</div>
            <div class="text-2xl font-bold text-gray-500">{{ number_format($stats['draft']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">รอชำระ</div>
            <div class="text-2xl font-bold text-orange-600">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ชำระแล้ว</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($stats['paid']) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ค้นหา..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">สถานะทั้งหมด</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอชำระ</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>

            <input type="date" name="from_date" value="{{ request('from_date') }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <input type="date" name="to_date" value="{{ request('to_date') }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เลขที่เอกสาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ผู้ขาย</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดรวม</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.accounting.expenses.show', $expense) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ $expense->document_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $expense->document_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $expense->contact->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($expense->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($expense->status === 'paid') bg-green-100 text-green-800
                                @elseif($expense->status === 'draft') bg-gray-100 text-gray-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $expense->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="{{ route('admin.accounting.expenses.show', $expense) }}"
                               class="text-blue-600 hover:text-blue-900 mr-3" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(auth()->user()->hasPermission('accounting.edit_expenses') || auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.accounting.expenses.edit', $expense) }}"
                               class="text-green-600 hover:text-green-900 mr-3" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('accounting.delete_expenses') || auth()->user()->isSuperAdmin())
                            <form action="{{ route('admin.accounting.expenses.destroy', $expense) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('ยืนยันการลบรายจ่ายนี้?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบข้อมูลรายจ่าย
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection
