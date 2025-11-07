@extends('layouts.admin')

@section('title', 'แก้ไขรายจ่าย')

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💰 แก้ไขรายจ่าย</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $expense->document_number }}</p>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.accounting.expenses.update', $expense) }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผู้ขาย *</label>
                    <select name="contact_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">เลือกผู้ขาย</option>
                        @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ $expense->contact_id == $contact->id ? 'selected' : '' }}>
                            {{ $contact->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เอกสาร *</label>
                    <input type="date" name="document_date" value="{{ old('document_date', $expense->document_date->format('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลขที่เอกสาร</label>
                    <input type="text" name="document_number" value="{{ old('document_number', $expense->document_number) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ *</label>
                    <select name="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="draft" {{ $expense->status === 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                        <option value="pending" {{ $expense->status === 'pending' ? 'selected' : '' }}>รอชำระ</option>
                        <option value="paid" {{ $expense->status === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes', $expense->notes) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('admin.accounting.expenses.show', $expense) }}"
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
@endsection
