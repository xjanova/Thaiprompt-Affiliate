@extends('layouts.admin-v3')

@section('title', 'แก้ไขประเภทการลา')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">แก้ไขประเภทการลา</h1>
                <p class="text-purple-100">{{ $leaveType->name }}</p>
            </div>
            <a href="{{ route('admin.hrm.leave.types') }}"
               class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.hrm.leave.types.update', $leaveType) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อประเภทการลา <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $leaveType->name) }}" required
                           placeholder="เช่น ลาพักร้อน, ลาป่วย"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white @error('name') border-red-500 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัส <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $leaveType->code) }}" required
                           placeholder="เช่น annual, sick"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white @error('code') border-red-500 @enderror">
                    @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500 mt-1">รหัสภาษาอังกฤษตัวพิมพ์เล็ก ไม่มีช่องว่าง</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white"
                              placeholder="อธิบายรายละเอียดของประเภทการลานี้">{{ old('description', $leaveType->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี</label>
                    <input type="color" name="color" value="{{ old('color', $leaveType->color ?? '#6366f1') }}"
                           class="w-full h-10 px-1 py-1 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">สีสำหรับแสดงในปฏิทิน</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $leaveType->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Entitlement -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สิทธิ์การลา</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนวันลาต่อปี <span class="text-red-500">*</span></label>
                    <input type="number" name="default_days_per_year" value="{{ old('default_days_per_year', $leaveType->default_days_per_year) }}" required step="0.5" min="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white @error('default_days_per_year') border-red-500 @enderror">
                    @error('default_days_per_year')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลาติดต่อกันสูงสุด (วัน)</label>
                    <input type="number" name="max_consecutive_days" value="{{ old('max_consecutive_days', $leaveType->max_consecutive_days) }}" min="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">เว้นว่างหากไม่จำกัด</p>
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="carry_forward" id="carry_forward" value="1" {{ old('carry_forward', $leaveType->carry_forward) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                               onchange="document.getElementById('carry_forward_options').classList.toggle('hidden')">
                        <label for="carry_forward" class="ml-2 text-sm text-gray-700 dark:text-gray-300">สามารถเก็บสะสมวันลาไปปีถัดไป</label>
                    </div>
                </div>

                <div id="carry_forward_options" class="{{ old('carry_forward', $leaveType->carry_forward) ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เก็บสะสมสูงสุด (วัน)</label>
                    <input type="number" name="max_carry_forward_days" value="{{ old('max_carry_forward_days', $leaveType->max_carry_forward_days) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">เว้นว่างหากไม่จำกัด</p>
                </div>
            </div>
        </div>

        <!-- Leave Rules -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">กฎการลา</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แจ้งล่วงหน้าขั้นต่ำ (วัน)</label>
                    <input type="number" name="min_advance_notice_days" value="{{ old('min_advance_notice_days', $leaveType->min_advance_notice_days) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">0 = ไม่จำเป็นต้องแจ้งล่วงหน้า</p>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_paid" id="is_paid" value="1" {{ old('is_paid', $leaveType->is_paid) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="is_paid" class="ml-2 text-sm text-gray-700 dark:text-gray-300">ได้รับค่าจ้าง</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="requires_approval" id="requires_approval" value="1" {{ old('requires_approval', $leaveType->requires_approval) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="requires_approval" class="ml-2 text-sm text-gray-700 dark:text-gray-300">ต้องได้รับการอนุมัติ</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="requires_document" id="requires_document" value="1" {{ old('requires_document', $leaveType->requires_document) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="requires_document" class="ml-2 text-sm text-gray-700 dark:text-gray-300">ต้องแนบเอกสารประกอบ</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics (Read-only) -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถิติการใช้งาน</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ใบลาทั้งหมด</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $leaveType->leaveRequests()->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">รออนุมัติ</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $leaveType->leaveRequests()->where('status', 'pending')->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">อนุมัติแล้ว</p>
                    <p class="text-2xl font-bold text-green-600">{{ $leaveType->leaveRequests()->where('status', 'approved')->count() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ไม่อนุมัติ</p>
                    <p class="text-2xl font-bold text-red-600">{{ $leaveType->leaveRequests()->where('status', 'rejected')->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.hrm.leave.types') }}"
               class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection
