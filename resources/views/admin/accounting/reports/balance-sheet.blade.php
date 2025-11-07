@extends('layouts.admin')

@section('title', 'งบดุล')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.reports.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">งบดุล</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Balance Sheet</p>
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
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="date" name="as_of_date" value="{{ request('as_of_date', now()->format('Y-m-d')) }}"
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
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">งบดุล</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    ณ วันที่ {{ request('as_of_date', now()->format('d/m/Y')) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <!-- Assets -->
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 border-b-2 pb-2">สินทรัพย์ (Assets)</h3>

                <!-- Current Assets -->
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">สินทรัพย์หมุนเวียน</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>เงินสดและเงินฝากธนาคาร</span>
                            <span>฿{{ number_format($report['cash'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>ลูกหนี้การค้า</span>
                            <span>฿{{ number_format($report['accounts_receivable'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>สินค้าคงเหลือ</span>
                            <span>฿{{ number_format($report['inventory'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white border-t pt-2">
                            <span>รวมสินทรัพย์หมุนเวียน</span>
                            <span>฿{{ number_format($report['total_current_assets'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Fixed Assets -->
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">สินทรัพย์ไม่หมุนเวียน</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>ที่ดิน อาคาร และอุปกรณ์</span>
                            <span>฿{{ number_format($report['fixed_assets'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>หัก: ค่าเสื่อมราคาสะสม</span>
                            <span>(฿{{ number_format($report['accumulated_depreciation'] ?? 0, 2) }})</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white border-t pt-2">
                            <span>รวมสินทรัพย์ไม่หมุนเวียน</span>
                            <span>฿{{ number_format($report['total_fixed_assets'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Assets -->
                <div class="border-t-2 border-blue-600 pt-4">
                    <div class="flex justify-between text-lg font-bold text-blue-600">
                        <span>รวมสินทรัพย์</span>
                        <span>฿{{ number_format($report['total_assets'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Liabilities & Equity -->
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 border-b-2 pb-2">หนี้สินและส่วนของเจ้าของ</h3>

                <!-- Current Liabilities -->
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">หนี้สินหมุนเวียน</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>เจ้าหนี้การค้า</span>
                            <span>฿{{ number_format($report['accounts_payable'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>ภาษีค้างจ่าย</span>
                            <span>฿{{ number_format($report['tax_payable'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>หนี้สินหมุนเวียนอื่น</span>
                            <span>฿{{ number_format($report['other_current_liabilities'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white border-t pt-2">
                            <span>รวมหนี้สินหมุนเวียน</span>
                            <span>฿{{ number_format($report['total_current_liabilities'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Long-term Liabilities -->
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">หนี้สินไม่หมุนเวียน</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>เงินกู้ยืมระยะยาว</span>
                            <span>฿{{ number_format($report['long_term_debt'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white border-t pt-2">
                            <span>รวมหนี้สินไม่หมุนเวียน</span>
                            <span>฿{{ number_format($report['total_long_term_liabilities'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Liabilities -->
                <div class="mb-6 border-t pt-2">
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white">
                        <span>รวมหนี้สิน</span>
                        <span>฿{{ number_format($report['total_liabilities'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <!-- Equity -->
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">ส่วนของเจ้าของ</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>ทุนจดทะเบียน</span>
                            <span>฿{{ number_format($report['capital'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>กำไรสะสม</span>
                            <span>฿{{ number_format($report['retained_earnings'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white border-t pt-2">
                            <span>รวมส่วนของเจ้าของ</span>
                            <span>฿{{ number_format($report['total_equity'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Liabilities & Equity -->
                <div class="border-t-2 border-blue-600 pt-4">
                    <div class="flex justify-between text-lg font-bold text-blue-600">
                        <span>รวมหนี้สินและส่วนของเจ้าของ</span>
                        <span>฿{{ number_format($report['total_liabilities_equity'] ?? 0, 2) }}</span>
                    </div>
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
