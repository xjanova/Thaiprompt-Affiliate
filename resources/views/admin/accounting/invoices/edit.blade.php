@extends('layouts.admin')

@section('title', 'แก้ไขใบแจ้งหนี้')

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📄 แก้ไขใบแจ้งหนี้</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $invoice->document_number }}</p>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.accounting.invoices.update', $invoice) }}" method="POST" id="invoice-form" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            @csrf
            @method('PUT')

            <!-- Document Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">บริษัท *</label>
                    <select name="company_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">เลือกบริษัท</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ $invoice->company_id == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทเอกสาร</label>
                    <div class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 bg-gray-50">
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลูกค้า *</label>
                    <select name="contact_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">เลือกลูกค้า</option>
                        @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ $invoice->contact_id == $contact->id ? 'selected' : '' }}>
                            {{ $contact->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เอกสาร *</label>
                    <input type="date" name="document_date" value="{{ old('document_date', $invoice->document_date->format('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่ครบกำหนด</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลขที่อ้างอิง</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number', $invoice->reference_number) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="PO-XXX หรืออื่นๆ">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ *</label>
                    <select name="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="draft" {{ $invoice->status == 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                        <option value="pending" {{ $invoice->status == 'pending' ? 'selected' : '' }}>รอชำระ</option>
                        <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                        <option value="partial" {{ $invoice->status == 'partial' ? 'selected' : '' }}>ชำระบางส่วน</option>
                        <option value="overdue" {{ $invoice->status == 'overdue' ? 'selected' : '' }}>เกินกำหนด</option>
                        <option value="cancelled" {{ $invoice->status == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>
            </div>

            <!-- Line Items -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">รายการสินค้า/บริการ</h3>
                <div class="overflow-x-auto">
                    <table class="w-full" id="line-items-table">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">รายการ</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">จำนวน</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300">หน่วย</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">ราคา/หน่วย</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">ส่วนลด</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">VAT %</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300">รวม</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="line-items">
                            <!-- Items will be added here dynamically -->
                        </tbody>
                    </table>
                </div>
                <button type="button" onclick="addLineItem()" class="mt-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus mr-2"></i>เพิ่มรายการ
                </button>
            </div>

            <!-- Discount and Notes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ส่วนลดท้ายบิล</label>
                    <input type="number" name="discount_amount" value="{{ old('discount_amount', $invoice->discount_amount) }}" min="0" step="0.01"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           onchange="calculateTotal()">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</label>
                <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('note', $invoice->note) }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เงื่อนไขการชำระเงิน</label>
                <textarea name="terms" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('terms', $invoice->terms) }}</textarea>
            </div>

            <!-- Totals -->
            <div class="border-t pt-6 mb-6">
                <div class="flex justify-end">
                    <div class="w-80">
                        <div class="flex justify-between mb-2 text-gray-700 dark:text-gray-300">
                            <span>ยอดรวมก่อน VAT:</span>
                            <span id="subtotal">฿0.00</span>
                        </div>
                        <div class="flex justify-between mb-2 text-gray-700 dark:text-gray-300">
                            <span>VAT:</span>
                            <span id="vat">฿0.00</span>
                        </div>
                        <div class="flex justify-between mb-2 text-gray-700 dark:text-gray-300">
                            <span>ส่วนลดท้ายบิล:</span>
                            <span id="discount">฿0.00</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-gray-300 dark:border-gray-600">
                            <span class="font-bold text-lg text-gray-900 dark:text-white">ยอดรวมทั้งสิ้น:</span>
                            <span class="font-bold text-lg text-gray-900 dark:text-white" id="total">฿0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('admin.accounting.invoices.show', $invoice) }}"
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-save mr-2"></i>บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = 0;
const products = @json($products);
const existingItems = @json($invoice->items);

function addLineItem(item = null) {
    const tbody = document.getElementById('line-items');
    const row = document.createElement('tr');
    row.className = 'item-row';

    const itemId = item ? item.id : '';
    const productId = item ? item.product_id : '';
    const description = item ? item.description : '';
    const quantity = item ? item.quantity : '1';
    const unit = item ? item.unit : 'ชิ้น';
    const unitPrice = item ? item.unit_price : '0';
    const discountAmount = item ? item.discount_amount : '0';
    const taxRate = item ? item.tax_rate : '7';

    row.innerHTML = `
        ${itemId ? `<input type="hidden" name="items[${itemIndex}][id]" value="${itemId}">` : ''}
        <td class="px-4 py-2">
            <select name="items[${itemIndex}][product_id]" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" onchange="selectProduct(this, ${itemIndex})">
                <option value="">เลือกสินค้า/บริการ</option>
                ${products.map(p => `<option value="${p.id}" data-price="${p.price}" data-unit="${p.unit}" ${p.id == productId ? 'selected' : ''}>${p.name}</option>`).join('')}
            </select>
            <input type="text" name="items[${itemIndex}][description]" required value="${description}" placeholder="รายละเอียด" class="w-full mt-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${itemIndex}][quantity]" required min="0.01" step="0.01" value="${quantity}" class="w-20 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-2">
            <input type="text" name="items[${itemIndex}][unit]" required value="${unit}" class="w-16 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-center">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${itemIndex}][unit_price]" required min="0" step="0.01" value="${unitPrice}" class="w-28 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${itemIndex}][discount_amount]" min="0" step="0.01" value="${discountAmount}" class="w-24 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${itemIndex}][tax_rate]" min="0" max="100" step="0.01" value="${taxRate}" class="w-16 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right" onchange="calculateRowTotal(${itemIndex})">
        </td>
        <td class="px-4 py-2 text-right">
            <span class="row-total font-semibold text-gray-900 dark:text-white">฿0.00</span>
        </td>
        <td class="px-4 py-2 text-center">
            <button type="button" onclick="removeLineItem(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    calculateRowTotal(itemIndex);
    itemIndex++;
}

function selectProduct(select, index) {
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const row = select.closest('tr');
        row.querySelector(`input[name="items[${index}][description]"]`).value = option.text;
        row.querySelector(`input[name="items[${index}][unit_price]"]`).value = option.dataset.price || 0;
        row.querySelector(`input[name="items[${index}][unit]"]`).value = option.dataset.unit || 'ชิ้น';
        calculateRowTotal(index);
    }
}

function calculateRowTotal(index) {
    const row = document.querySelector(`input[name="items[${index}][quantity]"]`)?.closest('tr');
    if (!row) return;

    const quantity = parseFloat(row.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
    const unitPrice = parseFloat(row.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
    const discount = parseFloat(row.querySelector(`input[name="items[${index}][discount_amount]"]`).value) || 0;
    const taxRate = parseFloat(row.querySelector(`input[name="items[${index}][tax_rate]"]`).value) || 0;

    const subtotal = (quantity * unitPrice) - discount;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;

    row.querySelector('.row-total').textContent = `฿${total.toFixed(2)}`;
    calculateTotal();
}

function removeLineItem(button) {
    button.closest('tr').remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    let totalVat = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const inputs = row.querySelectorAll('input[type="number"]');
        let quantity = 0, unitPrice = 0, discount = 0, taxRate = 0;

        inputs.forEach(input => {
            if (input.name.includes('[quantity]')) quantity = parseFloat(input.value) || 0;
            else if (input.name.includes('[unit_price]')) unitPrice = parseFloat(input.value) || 0;
            else if (input.name.includes('[discount_amount]')) discount = parseFloat(input.value) || 0;
            else if (input.name.includes('[tax_rate]')) taxRate = parseFloat(input.value) || 0;
        });

        const itemSubtotal = (quantity * unitPrice) - discount;
        const itemVat = itemSubtotal * (taxRate / 100);

        subtotal += itemSubtotal;
        totalVat += itemVat;
    });

    const billDiscount = parseFloat(document.querySelector('input[name="discount_amount"]').value) || 0;
    const total = subtotal + totalVat - billDiscount;

    document.getElementById('subtotal').textContent = `฿${subtotal.toFixed(2)}`;
    document.getElementById('vat').textContent = `฿${totalVat.toFixed(2)}`;
    document.getElementById('discount').textContent = `฿${billDiscount.toFixed(2)}`;
    document.getElementById('total').textContent = `฿${total.toFixed(2)}`;
}

// Load existing items on page load
document.addEventListener('DOMContentLoaded', function() {
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => {
            addLineItem(item);
        });
    } else {
        addLineItem();
    }
});
</script>
@endsection
