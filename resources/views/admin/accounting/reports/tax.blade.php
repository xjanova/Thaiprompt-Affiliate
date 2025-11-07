@extends('layouts.admin')

@section('title', 'รายงานภาษี')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.reports.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายงานภาษี</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">ภาษีขาย ภาษีซื้อ และภาษีหัก ณ ที่จ่าย</p>
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

            <select name="tax_type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="all" {{ request('tax_type') === 'all' ? 'selected' : '' }}>ทุกประเภท</option>
                <option value="vat_sales" {{ request('tax_type') === 'vat_sales' ? 'selected' : '' }}>ภาษีขาย (VAT)</option>
                <option value="vat_purchase" {{ request('tax_type') === 'vat_purchase' ? 'selected' : '' }}>ภาษีซื้อ (VAT)</option>
                <option value="withholding" {{ request('tax_type') === 'withholding' ? 'selected' : '' }}>ภาษีหัก ณ ที่จ่าย</option>
            </select>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    <!-- Tax Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm text-gray-600 dark:text-gray-400 mb-2">ภาษีขาย (Output VAT)</h3>
            <div class="text-3xl font-bold text-green-600">฿{{ number_format($taxSummary['output_vat'] ?? 0, 2) }}</div>
            <p class="text-xs text-gray-500 mt-2">จากยอดขาย: ฿{{ number_format($taxSummary['total_sales'] ?? 0, 2) }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm text-gray-600 dark:text-gray-400 mb-2">ภาษีซื้อ (Input VAT)</h3>
            <div class="text-3xl font-bold text-orange-600">฿{{ number_format($taxSummary['input_vat'] ?? 0, 2) }}</div>
            <p class="text-xs text-gray-500 mt-2">จากยอดซื้อ: ฿{{ number_format($taxSummary['total_purchases'] ?? 0, 2) }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm text-gray-600 dark:text-gray-400 mb-2">ภาษีค้างชำระ (VAT Payable)</h3>
            <div class="text-3xl font-bold {{ ($taxSummary['vat_payable'] ?? 0) >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                ฿{{ number_format(abs($taxSummary['vat_payable'] ?? 0), 2) }}
            </div>
            <p class="text-xs text-gray-500 mt-2">
                {{ ($taxSummary['vat_payable'] ?? 0) >= 0 ? 'ต้องชำระ' : 'ขอคืน' }}
            </p>
        </div>
    </div>

    <!-- VAT Report -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายงานภาษีมูลค่าเพิ่ม (VAT)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เลขที่เอกสาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ลูกค้า/ผู้ขาย</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เลขผู้เสียภาษี</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">มูลค่าสินค้า</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">VAT 7%</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดรวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($vatTransactions ?? [] as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                            {{ $transaction->document_number }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->contact_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->tax_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($transaction->base_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-blue-600">
                            ฿{{ number_format($transaction->vat_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($transaction->total_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบข้อมูลภาษี
                        </td>
                    </tr>
                    @endforelse

                    @if(isset($vatTransactions) && count($vatTransactions) > 0)
                    <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                        <td colspan="4" class="px-6 py-4 text-sm text-gray-900 dark:text-white">รวมทั้งหมด</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            ฿{{ number_format($vatTransactions->sum('base_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600">
                            ฿{{ number_format($vatTransactions->sum('vat_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            ฿{{ number_format($vatTransactions->sum('total_amount'), 2) }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Withholding Tax Report -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ภาษีหัก ณ ที่จ่าย (Withholding Tax)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ผู้รับเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เลขผู้เสียภาษี</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดเงิน</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">อัตรา</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ภาษีหัก</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($withholdingTax ?? [] as $wht)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->payee_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->tax_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->wht_type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($wht->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            {{ $wht->rate }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-red-600">
                            ฿{{ number_format($wht->wht_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบข้อมูลภาษีหัก ณ ที่จ่าย
                        </td>
                    </tr>
                    @endforelse

                    @if(isset($withholdingTax) && count($withholdingTax) > 0)
                    <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                        <td colspan="4" class="px-6 py-4 text-sm text-gray-900 dark:text-white">รวมทั้งหมด</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            ฿{{ number_format($withholdingTax->sum('amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">-</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                            ฿{{ number_format($withholdingTax->sum('wht_amount'), 2) }}
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
function exportPDF() {
    alert('ฟังก์ชันส่งออก PDF จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
