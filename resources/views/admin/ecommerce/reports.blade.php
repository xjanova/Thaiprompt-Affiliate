@extends('layouts.admin')

@section('title', 'รายงานยอดขาย')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📈 รายงานยอดขาย</h1>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จากวันที่</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Sales Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขายรวม</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                ฿{{ number_format($salesReport->sum('revenue') ?? 0) }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนคำสั่งซื้อ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($salesReport->sum('orders') ?? 0) }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดเฉลี่ยต่อคำสั่งซื้อ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                ฿{{ $salesReport->sum('orders') > 0 ? number_format($salesReport->sum('revenue') / $salesReport->sum('orders')) : 0 }}
            </p>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">สินค้าขายดี</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อันดับ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดขาย</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topProducts ?? [] as $index => $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $product->sku }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $product->total_sales ?? 0 }} ชิ้น
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
