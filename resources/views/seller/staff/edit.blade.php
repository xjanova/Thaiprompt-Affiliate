@extends('layouts.seller')

@section('title', 'แก้ไข ' . $employee->full_name . ' - ' . ($store->store_name ?? 'ร้านค้า'))

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('seller.staff.index') }}"
           class="p-2 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                แก้ไขข้อมูลพนักงาน
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ $employee->employee_id }} - {{ $employee->full_name }}
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('seller.staff.update', $employee) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- ข้อมูลส่วนตัว --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลส่วนตัว</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (อังกฤษ) *</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">นามสกุล (อังกฤษ) *</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (ไทย)</label>
                    <input type="text" name="first_name_th" value="{{ old('first_name_th', $employee->first_name_th) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">นามสกุล (ไทย)</label>
                    <input type="text" name="last_name_th" value="{{ old('last_name_th', $employee->last_name_th) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเล่น</label>
                    <input type="text" name="nickname" value="{{ old('nickname', $employee->nickname) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" name="mobile_phone" value="{{ old('mobile_phone', $employee->mobile_phone) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมล</label>
                    <input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        {{-- ข้อมูลการจ้างงาน --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลการจ้างงาน</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แผนก</label>
                    <select name="department_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <option value="">-- เลือกแผนก --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตำแหน่ง</label>
                    <select name="position_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <option value="">-- เลือกตำแหน่ง --</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}" {{ old('position_id', $employee->position_id) == $pos->id ? 'selected' : '' }}>
                                {{ $pos->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทการจ้าง *</label>
                    <select name="employment_type" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <option value="full_time" {{ old('employment_type', $employee->employment_type) == 'full_time' ? 'selected' : '' }}>พนักงานประจำ</option>
                        <option value="part_time" {{ old('employment_type', $employee->employment_type) == 'part_time' ? 'selected' : '' }}>พาร์ทไทม์</option>
                        <option value="contract" {{ old('employment_type', $employee->employment_type) == 'contract' ? 'selected' : '' }}>สัญญาจ้าง</option>
                        <option value="intern" {{ old('employment_type', $employee->employment_type) == 'intern' ? 'selected' : '' }}>ฝึกงาน</option>
                        <option value="freelance" {{ old('employment_type', $employee->employment_type) == 'freelance' ? 'selected' : '' }}>ฟรีแลนซ์</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ *</label>
                    <select name="employment_status" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <option value="active" {{ old('employment_status', $employee->employment_status) == 'active' ? 'selected' : '' }}>ประจำ</option>
                        <option value="probation" {{ old('employment_status', $employee->employment_status) == 'probation' ? 'selected' : '' }}>ทดลองงาน</option>
                        <option value="notice_period" {{ old('employment_status', $employee->employment_status) == 'notice_period' ? 'selected' : '' }}>แจ้งลาออก</option>
                        <option value="resigned" {{ old('employment_status', $employee->employment_status) == 'resigned' ? 'selected' : '' }}>ลาออก</option>
                        <option value="terminated" {{ old('employment_status', $employee->employment_status) == 'terminated' ? 'selected' : '' }}>เลิกจ้าง</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เงินเดือน (บาท)</label>
                    <input type="number" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" min="0" step="0.01"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กะการทำงาน</label>
                    <select name="work_shift_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <option value="">-- เลือกกะ --</option>
                        @foreach($workShifts as $shift)
                            <option value="{{ $shift->id }}" {{ old('work_shift_id', $employee->posAssignment?->work_shift_id) == $shift->id ? 'selected' : '' }}>
                                {{ $shift->name }} ({{ $shift->time_range }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- การตั้งค่า POS --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">การตั้งค่า POS</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัส PIN ใหม่ (4-6 หลัก)</label>
                    <input type="password" name="pin_code" maxlength="6" placeholder="ปล่อยว่างถ้าไม่เปลี่ยน"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    <p class="mt-1 text-xs text-gray-500">ปล่อยว่างถ้าไม่ต้องการเปลี่ยนรหัส PIN</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สิทธิ์การใช้งาน POS</label>
                    @php $currentPerms = $employee->posAssignment?->pos_permissions ?? ['sales']; @endphp
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['sales' => 'ขายสินค้า', 'refund' => 'คืนเงิน', 'discount' => 'ให้ส่วนลด', 'reports' => 'ดูรายงาน', 'inventory' => 'จัดการสินค้า', 'drawer' => 'เปิดลิ้นชัก'] as $key => $label)
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="{{ $key }}"
                                   {{ in_array($key, $currentPerms) ? 'checked' : '' }}
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('seller.staff.index') }}"
               class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-lg transition font-medium">
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection
