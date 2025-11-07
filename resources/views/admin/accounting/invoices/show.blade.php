@extends('layouts.admin')

@section('title', 'รายละเอียดใบแจ้งหนี้')

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📄 รายละเอียดใบแจ้งหนี้</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $invoice->document_number }}</p>
            </div>
            <div class="flex gap-2">
                @if(auth()->user()->hasPermission('accounting.edit_invoices') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.invoices.edit', $invoice) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-edit mr-2"></i>แก้ไข
                </a>
                @endif
                <a href="{{ route('admin.accounting.invoices.index') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                    กลับ
                </a>
            </div>
        </div>

        <!-- Company and Contact Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Company Info -->
            @if($invoice->company)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลบริษัท</h3>
                <div class="space-y-2">
                    <div class="text-gray-900 dark:text-white font-semibold">{{ $invoice->company->name }}</div>
                    @if($invoice->company->tax_id)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">เลขที่ผู้เสียภาษี: {{ $invoice->company->tax_id }}</div>
                    @endif
                    @if($invoice->company->address)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">{{ $invoice->company->address }}</div>
                    @endif
                    @if($invoice->company->phone)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Tel: {{ $invoice->company->phone }}</div>
                    @endif
                    @if($invoice->company->email)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Email: {{ $invoice->company->email }}</div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลลูกค้า</h3>
                <div class="space-y-2">
                    <div class="text-gray-900 dark:text-white font-semibold">{{ $invoice->contact->name }}</div>
                    @if($invoice->contact->tax_id)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">เลขที่ผู้เสียภาษี: {{ $invoice->contact->tax_id }}</div>
                    @endif
                    @if($invoice->contact->address)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">{{ $invoice->contact->address }}</div>
                    @endif
                    @if($invoice->contact->phone)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Tel: {{ $invoice->contact->phone }}</div>
                    @endif
                    @if($invoice->contact->email)
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Email: {{ $invoice->contact->email }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Document Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">เลขที่เอกสาร</label>
                    <div class="text-gray-900 dark:text-white font-semibold">{{ $invoice->document_number }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ประเภท</label>
                    <div class="text-gray-900 dark:text-white">
                        @switch($invoice->document_type)
                            @case('invoice') ใบแจ้งหนี้ @break
                            @case('tax_invoice') ใบกำกับภาษี @break
                            @case('receipt') ใบเสร็จรับเงิน @break
                            @case('quotation') ใบเสนอราคา @break
                            @default {{ $invoice->document_type }}
                        @endswitch
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">วันที่</label>
                    <div class="text-gray-900 dark:text-white">{{ $invoice->document_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">วันครบกำหนด</label>
                    <div class="text-gray-900 dark:text-white">
                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
                    </div>
                </div>
                @if($invoice->reference_number)
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">เลขที่อ้างอิง</label>
                    <div class="text-gray-900 dark:text-white">{{ $invoice->reference_number }}</div>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                        @if($invoice->status === 'paid') bg-green-100 text-green-800
                        @elseif($invoice->status === 'draft') bg-gray-100 text-gray-800
                        @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                        @elseif($invoice->status === 'partial') bg-yellow-100 text-yellow-800
                        @else bg-blue-100 text-blue-800 @endif">
                        {{ $invoice->status }}
                    </span>
                </div>
            </div>

            @if($invoice->note)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">หมายเหตุ</label>
                <div class="text-gray-900 dark:text-white">{{ $invoice->note }}</div>
            </div>
            @endif

            @if($invoice->terms)
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">เงื่อนไขการชำระเงิน</label>
                <div class="text-gray-900 dark:text-white">{{ $invoice->terms }}</div>
            </div>
            @endif
        </div>

        <!-- Line Items -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายการ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หน่วย</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราคา/หน่วย</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ส่วนลด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">VAT</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoice->items as $index => $item)
                    <tr>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            <div class="font-medium">{{ $item->description }}</div>
                            @if($item->product)
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->product->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-6 py-4 text-center text-gray-900 dark:text-white">{{ $item->unit }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">฿{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">
                            @if($item->discount_amount > 0)
                            ฿{{ number_format($item->discount_amount, 2) }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white">
                            {{ number_format($item->tax_rate, 0) }}%
                        </td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white font-semibold">
                            ฿{{ number_format($item->amount, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td colspan="7" class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">ยอดรวมก่อน VAT:</td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">฿{{ number_format($invoice->subtotal_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">VAT:</td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">฿{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td colspan="7" class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">ส่วนลดท้ายบิล:</td>
                        <td class="px-6 py-3 text-right font-semibold text-red-600">-฿{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                        <td colspan="7" class="px-6 py-4 text-right font-bold text-lg text-gray-900 dark:text-white">ยอดรวมทั้งสิ้น:</td>
                        <td class="px-6 py-4 text-right font-bold text-lg text-gray-900 dark:text-white">฿{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                    @if($invoice->balance > 0)
                    <tr>
                        <td colspan="7" class="px-6 py-3 text-right font-semibold text-orange-600">คงเหลือ:</td>
                        <td class="px-6 py-3 text-right font-semibold text-orange-600">฿{{ number_format($invoice->balance, 2) }}</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        <!-- Payment Records -->
        @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ประวัติการชำระเงิน</h3>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วิธีชำระ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เลขที่อ้างอิง</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoice->payments as $payment)
                    <tr>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            @switch($payment->payment_method)
                                @case('cash') เงินสด @break
                                @case('bank_transfer') โอนเงิน @break
                                @case('credit_card') บัตรเครดิต @break
                                @case('cheque') เช็ค @break
                                @default {{ $payment->payment_method }}
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $payment->reference ?? '-' }}</td>
                        <td class="px-6 py-4 text-right text-gray-900 dark:text-white font-semibold">฿{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $payment->note ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <div>
                @if($invoice->balance > 0 && (auth()->user()->hasPermission('accounting.create_payments') || auth()->user()->isSuperAdmin()))
                <button type="button" onclick="showPaymentModal()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-money-bill mr-2"></i>บันทึกการชำระเงิน
                </button>
                @endif
            </div>
            <div class="flex gap-2">
                <button type="button" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                </button>
                <button type="button" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-envelope mr-2"></i>ส่ง Email
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showPaymentModal() {
    // TODO: Implement payment modal
    alert('ฟีเจอร์บันทึกการชำระเงินกำลังพัฒนา');
}
</script>
@endsection
