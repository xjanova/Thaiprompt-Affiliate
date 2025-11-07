@extends('layouts.admin')

@section('title', 'รายงานยอดขาย')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.reports.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายงานยอดขาย</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">สรุปยอดขายและรายได้</p>
            </div>
        </div>
        <div class="flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-print mr-2"></i>พิมพ์
            </button>
            <button onclick="exportExcel()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>ส่งออก Excel
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ยอดขายรวม</div>
            <div class="text-2xl font-bold text-green-600">฿{{ number_format($stats['total_sales'] ?? 0, 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">จำนวนใบแจ้งหนี้</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_invoices'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ยอดเฉลี่ย/ใบแจ้งหนี้</div>
            <div class="text-2xl font-bold text-purple-600">฿{{ number_format($stats['average_invoice'] ?? 0, 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">จำนวนลูกค้า</div>
            <div class="text-2xl font-bold text-orange-600">{{ number_format($stats['total_customers'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <select name="period" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>เดือนนี้</option>
                <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>เดือนที่แล้ว</option>
                <option value="this_quarter" {{ request('period') === 'this_quarter' ? 'selected' : '' }}>ไตรมาสนี้</option>
                <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }}>ปีนี้</option>
            </select>

            <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <select name="group_by" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="customer" {{ request('group_by') === 'customer' ? 'selected' : '' }}>จัดกลุ่มตามลูกค้า</option>
                <option value="product" {{ request('group_by') === 'product' ? 'selected' : '' }}>จัดกลุ่มตามสินค้า</option>
                <option value="date" {{ request('group_by') === 'date' ? 'selected' : '' }}>จัดกลุ่มตามวันที่</option>
            </select>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายละเอียดยอดขาย</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            @if(request('group_by') === 'product')
                                สินค้า/บริการ
                            @elseif(request('group_by') === 'date')
                                วันที่
                            @else
                                ลูกค้า
                            @endif
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">จำนวนรายการ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดรวม</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ส่วนลด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดสุทธิ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% จากยอดรวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($salesData ?? [] as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $item->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            {{ number_format($item->count) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($item->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-600">
                            ฿{{ number_format($item->discount ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600">
                            ฿{{ number_format($item->net_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            {{ number_format($item->percentage, 2) }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบข้อมูลยอดขาย
                        </td>
                    </tr>
                    @endforelse

                    @if(isset($salesData) && count($salesData) > 0)
                    <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">รวมทั้งหมด</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            {{ number_format($salesData->sum('count')) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            ฿{{ number_format($salesData->sum('total_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-600">
                            ฿{{ number_format($salesData->sum('discount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                            ฿{{ number_format($salesData->sum('net_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            100.00%
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportExcel() {
    alert('ฟังก์ชันส่งออก Excel จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
