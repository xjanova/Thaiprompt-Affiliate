@extends('layouts.admin-v3')

@section('title', 'รายละเอียดใบลา')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">รายละเอียดใบลา</h1>
                <p class="text-orange-100">{{ $leaveRequest->employee->full_name }} - {{ $leaveRequest->leaveType->name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.hrm.leave.index') }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    ← กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">สถานะ</p>
                <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full mt-1
                    {{ $leaveRequest->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $leaveRequest->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $leaveRequest->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $leaveRequest->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}">
                    {{ ucfirst($leaveRequest->status) }}
                </span>
            </div>
            @if($leaveRequest->status === 'pending')
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('approveModal').classList.remove('hidden')"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    อนุมัติ
                </button>
                <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    ไม่อนุมัติ
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Leave Information Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Employee Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>👤</span> ข้อมูลพนักงาน
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ชื่อ-นามสกุล</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->employee->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">รหัสพนักงาน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->employee->employee_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">แผนก</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->employee->department->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ตำแหน่ง</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->employee->position->title ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ผู้จัดการ</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->employee->manager->full_name ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Leave Details -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📋</span> รายละเอียดการลา
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ประเภทการลา</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        <span class="inline-block px-2 py-1 rounded" style="background-color: {{ $leaveRequest->leaveType->color ?? '#e5e7eb' }}20; color: {{ $leaveRequest->leaveType->color ?? '#6b7280' }}">
                            {{ $leaveRequest->leaveType->name }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">วันที่เริ่มลา</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $leaveRequest->start_date->format('d/m/Y') }}
                        <span class="text-xs text-gray-500">({{ $leaveRequest->start_day_type === 'full_day' ? 'เต็มวัน' : 'ครึ่งวัน' }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">วันที่สิ้นสุด</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $leaveRequest->end_date->format('d/m/Y') }}
                        <span class="text-xs text-gray-500">({{ $leaveRequest->end_day_type === 'full_day' ? 'เต็มวัน' : 'ครึ่งวัน' }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">จำนวนวัน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->total_days }} วัน</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ช่องทางติดต่อระหว่างลา</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->contact_during_leave ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Reason -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เหตุผลการลา</h3>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $leaveRequest->reason ?? '-' }}</p>
    </div>

    <!-- Attachment -->
    @if($leaveRequest->attachment)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เอกสารแนบ</h3>
        <a href="{{ Storage::url($leaveRequest->attachment) }}" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <span>📎</span> ดาวน์โหลดเอกสาร
        </a>
    </div>
    @endif

    <!-- Approval/Rejection Information -->
    @if($leaveRequest->status === 'approved' && $leaveRequest->approver)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span>✅</span> ข้อมูลการอนุมัติ
        </h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">ผู้อนุมัติ</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->approver->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">วันที่อนุมัติ</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->approved_at->format('d/m/Y H:i') }}</dd>
            </div>
            @if($leaveRequest->approval_remarks)
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">หมายเหตุ</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->approval_remarks }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @endif

    @if($leaveRequest->status === 'rejected' && $leaveRequest->rejector)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span>❌</span> ข้อมูลการไม่อนุมัติ
        </h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">ผู้ไม่อนุมัติ</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->rejector->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">วันที่ไม่อนุมัติ</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->rejected_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">เหตุผล</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $leaveRequest->rejection_reason }}</dd>
            </div>
        </dl>
    </div>
    @endif
</div>

<!-- Approve Modal -->
<div id="approveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">อนุมัติใบลา</h3>
        <form method="POST" action="{{ route('admin.hrm.leave.approve', $leaveRequest) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                <textarea name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-slate-700 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('approveModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    ยืนยันอนุมัติ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ไม่อนุมัติใบลา</h3>
        <form method="POST" action="{{ route('admin.hrm.leave.reject', $leaveRequest) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-slate-700 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    ยืนยันไม่อนุมัติ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
