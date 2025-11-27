@extends('layouts.seller')

@section('title', 'เพิ่มพนักงานใหม่ - ' . ($store->store_name ?? 'ร้านค้า'))

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
                เพิ่มพนักงานใหม่
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                กรอกข้อมูลพนักงานเพื่อเพิ่มเข้าระบบ
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('seller.staff.store') }}" class="space-y-6">
        @csrf

        {{-- ข้อมูลส่วนตัว --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                ข้อมูลส่วนตัว
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อ (อังกฤษ) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        นามสกุล (อังกฤษ) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อ (ไทย)
                    </label>
                    <input type="text" name="first_name_th" value="{{ old('first_name_th') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        นามสกุล (ไทย)
                    </label>
                    <input type="text" name="last_name_th" value="{{ old('last_name_th') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อเล่น
                    </label>
                    <input type="text" name="nickname" value="{{ old('nickname') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        เบอร์โทรศัพท์
                    </label>
                    <input type="tel" name="mobile_phone" value="{{ old('mobile_phone') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        อีเมล
                    </label>
                    <input type="email" name="personal_email" value="{{ old('personal_email') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
            </div>
        </div>

        {{-- ข้อมูลการจ้างงาน --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                ข้อมูลการจ้างงาน
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        แผนก
                    </label>
                    <select name="department_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- เลือกแผนก --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">ถ้าไม่เลือก ระบบจะสร้างแผนก "ทั่วไป" ให้อัตโนมัติ</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ตำแหน่ง
                    </label>
                    <select name="position_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- เลือกตำแหน่ง --</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}" {{ old('position_id') == $pos->id ? 'selected' : '' }}>
                                {{ $pos->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">ถ้าไม่เลือก ระบบจะสร้างตำแหน่ง "พนักงาน" ให้อัตโนมัติ</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        วันเริ่มงาน <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ประเภทการจ้าง <span class="text-red-500">*</span>
                    </label>
                    <select name="employment_type" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="full_time" {{ old('employment_type') == 'full_time' ? 'selected' : '' }}>พนักงานประจำ (Full-time)</option>
                        <option value="part_time" {{ old('employment_type') == 'part_time' ? 'selected' : '' }}>พนักงานพาร์ทไทม์ (Part-time)</option>
                        <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>พนักงานสัญญาจ้าง (Contract)</option>
                        <option value="intern" {{ old('employment_type') == 'intern' ? 'selected' : '' }}>นักศึกษาฝึกงาน (Intern)</option>
                        <option value="freelance" {{ old('employment_type') == 'freelance' ? 'selected' : '' }}>ฟรีแลนซ์ (Freelance)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        เงินเดือน (บาท)
                    </label>
                    <input type="number" name="basic_salary" value="{{ old('basic_salary') }}" min="0" step="0.01"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        กะการทำงาน
                    </label>
                    <select name="work_shift_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- เลือกกะ --</option>
                        @foreach($workShifts as $shift)
                            <option value="{{ $shift->id }}" {{ old('work_shift_id') == $shift->id ? 'selected' : '' }}>
                                {{ $shift->name }} ({{ $shift->time_range }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- การตั้งค่า POS --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                การตั้งค่า POS
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        รหัส PIN สำหรับ Clock-In (4-6 หลัก)
                    </label>
                    <input type="password" name="pin_code" maxlength="6" placeholder="เช่น 1234"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <p class="mt-1 text-xs text-gray-500">ใส่เพื่อให้พนักงานสามารถลงเวลาที่เครื่อง POS ได้</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สิทธิ์การใช้งาน POS
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="sales" checked
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ขายสินค้า</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="refund"
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">คืนเงิน</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="discount"
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ให้ส่วนลด</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="reports"
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ดูรายงาน</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="inventory"
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">จัดการสินค้า</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input type="checkbox" name="pos_permissions[]" value="drawer"
                                   class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">เปิดลิ้นชัก</span>
                        </label>
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
                บันทึกพนักงาน
            </button>
        </div>
    </form>
</div>
@endsection
