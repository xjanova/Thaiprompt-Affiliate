@extends('layouts.admin')

@section('title', 'จัดการประเภทการลา')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">📋 จัดการประเภทการลา</h1>
                <p class="text-purple-100">กำหนดและจัดการประเภทการลาต่างๆ</p>
            </div>
            <a href="{{ route('admin.hrm.leave.types.create') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition">
                + เพิ่มประเภทการลา
            </a>
        </div>
    </div>

    <!-- Leave Types Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($leaveTypes as $type)
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
            <!-- Header with color -->
            <div class="p-4 border-l-4" style="border-color: {{ $type->color ?? '#6b7280' }}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $type->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $type->code }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full {{ $type->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $type->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                @if($type->description)
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">{{ $type->description }}</p>
                @endif

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">วันลาต่อปี</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $type->default_days_per_year }} วัน</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ใบลาทั้งหมด</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $type->leave_requests_count }} ใบ</p>
                    </div>
                    @if($type->max_consecutive_days)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ลาติดต่อกันสูงสุด</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $type->max_consecutive_days }} วัน</p>
                    </div>
                    @endif
                    @if($type->min_advance_notice_days > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">แจ้งล่วงหน้า</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $type->min_advance_notice_days }} วัน</p>
                    </div>
                    @endif
                </div>

                <!-- Badges -->
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($type->is_paid)
                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">ได้รับค่าจ้าง</span>
                    @else
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">ไม่ได้รับค่าจ้าง</span>
                    @endif

                    @if($type->requires_approval)
                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">ต้องอนุมัติ</span>
                    @endif

                    @if($type->requires_document)
                    <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded">ต้องแนบเอกสาร</span>
                    @endif

                    @if($type->carry_forward)
                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">เก็บสะสมได้</span>
                    @if($type->max_carry_forward_days)
                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">สะสมสูงสุด {{ $type->max_carry_forward_days }} วัน</span>
                    @endif
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex gap-2 border-t pt-3">
                    <a href="{{ route('admin.hrm.leave.types.edit', $type) }}"
                       class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                        แก้ไข
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white dark:bg-slate-800 rounded-lg shadow-md p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีประเภทการลา</p>
            <a href="{{ route('admin.hrm.leave.types.create') }}"
               class="inline-block px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                + เพิ่มประเภทการลาแรก
            </a>
        </div>
        @endforelse
    </div>

    <!-- Quick Stats -->
    @if($leaveTypes->isNotEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถิติรวม</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600">{{ $leaveTypes->count() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">ประเภททั้งหมด</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">{{ $leaveTypes->where('is_active', true)->count() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">กำลังใช้งาน</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600">{{ $leaveTypes->where('is_paid', true)->count() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">ได้รับค่าจ้าง</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-orange-600">{{ $leaveTypes->sum('leave_requests_count') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">ใบลาทั้งหมด</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
