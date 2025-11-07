@extends('layouts.admin')

@section('title', 'แก้ไขผู้ติดต่อ')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center mb-4">
            <a href="{{ route('admin.accounting.contacts.show', $contact) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">แก้ไขผู้ติดต่อ</h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400">แก้ไขข้อมูลผู้ติดต่อ: {{ $contact->name }}</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.accounting.contacts.update', $contact) }}" method="POST" class="max-w-4xl">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
            <!-- Contact Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภทผู้ติดต่อ <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600">
                        <input type="checkbox" name="is_customer" value="1" {{ old('is_customer', $contact->is_customer) ? 'checked' : '' }} class="mr-3">
                        <div>
                            <i class="fas fa-user text-blue-600 mr-2"></i>
                            <span class="font-medium text-gray-900 dark:text-white">ลูกค้า</span>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600">
                        <input type="checkbox" name="is_vendor" value="1" {{ old('is_vendor', $contact->is_vendor) ? 'checked' : '' }} class="mr-3">
                        <div>
                            <i class="fas fa-building text-orange-600 mr-2"></i>
                            <span class="font-medium text-gray-900 dark:text-white">ผู้ขาย</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อ-นามสกุล / ชื่อบริษัท <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $contact->name) }}" required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="ชื่อผู้ติดต่อ">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Company Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อบริษัท (ถ้ามี)
                        </label>
                        <input type="text" name="company_name" value="{{ old('company_name', $contact->company_name) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="ชื่อบริษัท">
                        @error('company_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            อีเมล
                        </label>
                        <input type="email" name="email" value="{{ old('email', $contact->email) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="email@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            โทรศัพท์
                        </label>
                        <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="0812345678">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tax ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เลขประจำตัวผู้เสียภาษี
                        </label>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $contact->tax_id) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="1234567890123">
                        @error('tax_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ที่อยู่</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ที่อยู่</label>
                        <textarea name="address" rows="2"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  placeholder="บ้านเลขที่, ถนน">{{ old('address', $contact->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เขต/อำเภอ</label>
                            <input type="text" name="district" value="{{ old('district', $contact->district) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จังหวัด</label>
                            <input type="text" name="province" value="{{ old('province', $contact->province) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสไปรษณีย์</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $contact->postal_code) }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes & Status -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                              placeholder="ข้อมูลเพิ่มเติม">{{ old('notes', $contact->notes) }}</textarea>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $contact->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้งาน</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>บันทึก
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
