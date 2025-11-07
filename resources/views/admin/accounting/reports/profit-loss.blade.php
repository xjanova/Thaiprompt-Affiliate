@extends('layouts.admin')

@section('title', 'รายงานกำไร-ขาดทุน')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.reports.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายงานกำไร-ขาดทุน</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Profit & Loss Statement</p>
            </div>
        </div>
        <div class="flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-print mr-2"></i>พิมพ์
            </button>
            <button onclick="exportPDF()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-file-pdf mr-2"></i>ส่งออก PDF
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select name="period" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>เดือนนี้</option>
                <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>เดือนที่แล้ว</option>
                <option value="this_quarter" {{ request('period') === 'this_quarter' ? 'selected' : '' }}>ไตรมาสนี้</option>
                <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }}>ปีนี้</option>
                <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>กำหนดเอง</option>
            </select>

            <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    <!-- Report -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายงานกำไร-ขาดทุน</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    ระหว่างวันที่ {{ request('from_date', now()->startOfMonth()->format('d/m/Y')) }}
                    ถึง {{ request('to_date', now()->format('d/m/Y')) }}
                </p>
            </div>
        </div>

        <div class="p-6">
            <!-- Revenue Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">รายได้</h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>รายได้จากการขาย</span>
                        <span>฿{{ number_format($report['sales_revenue'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>รายได้อื่นๆ</span>
                        <span>฿{{ number_format($report['other_revenue'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white border-t pt-2">
                        <span>รายได้รวม</span>
                        <span class="text-green-600">฿{{ number_format($report['total_revenue'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Cost of Goods Sold -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">ต้นทุนขาย</h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>ต้นทุนสินค้า/บริการ</span>
                        <span>฿{{ number_format($report['cost_of_goods'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white border-t pt-2">
                        <span>กำไรขั้นต้น</span>
                        <span class="text-green-600">฿{{ number_format($report['gross_profit'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Operating Expenses -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">ค่าใช้จ่ายในการดำเนินงาน</h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>ค่าเช่า</span>
                        <span>฿{{ number_format($report['rent_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินเดือนและค่าจ้าง</span>
                        <span>฿{{ number_format($report['salary_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>ค่าสาธารณูปโภค</span>
                        <span>฿{{ number_format($report['utilities_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>ค่าใช้จ่ายอื่นๆ</span>
                        <span>฿{{ number_format($report['other_expenses'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white border-t pt-2">
                        <span>รวมค่าใช้จ่าย</span>
                        <span class="text-orange-600">฿{{ number_format($report['total_expenses'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Net Income -->
            <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-4">
                <div class="flex justify-between text-xl font-bold">
                    <span class="text-gray-900 dark:text-white">กำไร(ขาดทุน)สุทธิ</span>
                    <span class="{{ ($report['net_income'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ฿{{ number_format($report['net_income'] ?? 0, 2) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mt-2">
                    <span>อัตรากำไรสุทธิ</span>
                    <span>{{ number_format($report['profit_margin'] ?? 0, 2) }}%</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                สร้างรายงานเมื่อ: {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportPDF() {
    alert('ฟังก์ชันส่งออก PDF จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
