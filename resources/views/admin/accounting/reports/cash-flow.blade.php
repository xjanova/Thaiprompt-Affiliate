@extends('layouts.admin')

@section('title', 'รายงานกระแสเงินสด')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.reports.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายงานกระแสเงินสด</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Cash Flow Statement</p>
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
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายงานกระแสเงินสด</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    ระหว่างวันที่ {{ request('from_date', now()->startOfMonth()->format('d/m/Y')) }}
                    ถึง {{ request('to_date', now()->format('d/m/Y')) }}
                </p>
            </div>
        </div>

        <div class="p-6">
            <!-- Operating Activities -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                    กระแสเงินสดจากกิจกรรมดำเนินงาน
                </h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดรับจากลูกค้า</span>
                        <span class="text-green-600">฿{{ number_format($report['cash_from_customers'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดจ่ายให้ผู้ขาย</span>
                        <span class="text-red-600">(฿{{ number_format($report['cash_to_suppliers'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดจ่ายค่าใช้จ่ายดำเนินงาน</span>
                        <span class="text-red-600">(฿{{ number_format($report['operating_expenses_paid'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดจ่ายภาษีเงินได้</span>
                        <span class="text-red-600">(฿{{ number_format($report['tax_paid'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white border-t pt-2">
                        <span>เงินสดสุทธิจากกิจกรรมดำเนินงาน</span>
                        <span class="{{ ($report['net_cash_operating'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ฿{{ number_format($report['net_cash_operating'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Investing Activities -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                    กระแสเงินสดจากกิจกรรมลงทุน
                </h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดจ่ายซื้อสินทรัพย์</span>
                        <span class="text-red-600">(฿{{ number_format($report['purchase_of_assets'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดรับจากขายสินทรัพย์</span>
                        <span class="text-green-600">฿{{ number_format($report['sale_of_assets'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white border-t pt-2">
                        <span>เงินสดสุทธิจากกิจกรรมลงทุน</span>
                        <span class="{{ ($report['net_cash_investing'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ฿{{ number_format($report['net_cash_investing'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financing Activities -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b pb-2">
                    กระแสเงินสดจากกิจกรรมจัดหาเงิน
                </h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดรับจากเงินกู้</span>
                        <span class="text-green-600">฿{{ number_format($report['loan_proceeds'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดจ่ายชำระเงินกู้</span>
                        <span class="text-red-600">(฿{{ number_format($report['loan_repayment'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>เงินสดจ่ายเงินปันผล</span>
                        <span class="text-red-600">(฿{{ number_format($report['dividends_paid'] ?? 0, 2) }})</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white border-t pt-2">
                        <span>เงินสดสุทธิจากกิจกรรมจัดหาเงิน</span>
                        <span class="{{ ($report['net_cash_financing'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ฿{{ number_format($report['net_cash_financing'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Net Change in Cash -->
            <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-4 space-y-2">
                <div class="flex justify-between font-bold text-gray-900 dark:text-white">
                    <span>เงินสดและรายการเทียบเท่าเงินสดเพิ่มขึ้น(ลดลง)สุทธิ</span>
                    <span class="{{ ($report['net_change_in_cash'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ฿{{ number_format($report['net_change_in_cash'] ?? 0, 2) }}
                    </span>
                </div>
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>เงินสดต้นงวด</span>
                    <span>฿{{ number_format($report['beginning_cash'] ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-xl font-bold text-blue-600 border-t-2 pt-2">
                    <span>เงินสดปลายงวด</span>
                    <span>฿{{ number_format($report['ending_cash'] ?? 0, 2) }}</span>
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
