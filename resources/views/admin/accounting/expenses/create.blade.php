@extends('layouts.admin')

@section('title', 'สร้างรายจ่าย')

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💰 สร้างรายจ่าย</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">บันทึกรายจ่ายใหม่</p>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.accounting.expenses.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผู้ขาย *</label>
                    <select name="contact_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">เลือกผู้ขาย</option>
                        @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เอกสาร *</label>
                    <input type="date" name="document_date" value="{{ old('document_date', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลขที่เอกสาร</label>
                    <input type="text" name="document_number" value="{{ old('document_number') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="ระบบจะสร้างเลขที่อัตโนมัติ">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ *</label>
                    <select name="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="draft">แบบร่าง</option>
                        <option value="pending">รอชำระ</option>
                        <option value="paid">ชำระแล้ว</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes') }}</textarea>
            </div>

            <!-- Line Items -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">รายการสินค้า/บริการ</h3>
                <div id="line-items">
                    <!-- Items will be added here dynamically -->
                </div>
                <button type="button" onclick="addLineItem()" class="mt-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus mr-2"></i>เพิ่มรายการ
                </button>
            </div>

            <!-- Totals -->
            <div class="border-t pt-6 mb-6">
                <div class="flex justify-end">
                    <div class="w-64">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600 dark:text-gray-400">ยอดรวม:</span>
                            <span class="font-bold text-gray-900 dark:text-white" id="total">฿0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('admin.accounting.expenses.index') }}"
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
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
function addLineItem() {
    // Simplified - you should implement proper line item management
    alert('Line item management needs to be implemented');
}
</script>
@endsection
