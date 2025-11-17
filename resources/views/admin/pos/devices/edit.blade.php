@extends('layouts.admin-v3')

@section('title', 'แก้ไขอุปกรณ์ POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">✏️ แก้ไขอุปกรณ์ POS</h1>
            <p class="text-gray-500 mt-1">{{ $device->device_name }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.pos.devices.show', $device) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.pos.devices.update', $device) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ร้านค้า <span class="text-red-600">*</span>
                    </label>
                    <select name="store_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('store_id') border-red-500 @enderror">
                        <option value="">เลือกร้านค้า</option>
                        @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ (old('store_id', $device->store_id) == $store->id) ? 'selected' : '' }}>
                            {{ $store->store_name }} ({{ $store->store_code }})
                        </option>
                        @endforeach
                    </select>
                    @error('store_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ชื่ออุปกรณ์ <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="device_name" value="{{ old('device_name', $device->device_name) }}" required
                        placeholder="เช่น POS Counter 1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('device_name') border-red-500 @enderror">
                    @error('device_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ประเภทอุปกรณ์ <span class="text-red-600">*</span>
                    </label>
                    <select name="device_type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('device_type') border-red-500 @enderror">
                        <option value="">เลือกประเภท</option>
                        <option value="standard" {{ old('device_type', $device->device_type) == 'standard' ? 'selected' : '' }}>📱 Standard</option>
                        <option value="premium" {{ old('device_type', $device->device_type) == 'premium' ? 'selected' : '' }}>💎 Premium</option>
                    </select>
                    @error('device_type')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Hardware ID
                    </label>
                    <input type="text" name="hardware_id" value="{{ old('hardware_id', $device->hardware_id) }}"
                        placeholder="ไม่บังคับ"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('hardware_id') border-red-500 @enderror">
                    @error('hardware_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Device Code
                    </label>
                    <input type="text" value="{{ $device->device_code }}" disabled
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">รหัสอุปกรณ์ถูกสร้างอัตโนมัติและไม่สามารถแก้ไขได้</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        License Key
                    </label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $device->license_key }}" disabled
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 font-mono text-sm">
                        <a href="{{ route('admin.pos.devices.show', $device) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            จัดการ License
                        </a>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">License Key สามารถสร้างใหม่ได้ในหน้ารายละเอียดอุปกรณ์</p>
                </div>
            </div>
        </div>

        <!-- Subscription Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลแพคเกจ</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        สถานะแพคเกจ <span class="text-red-600">*</span>
                    </label>
                    <select name="subscription_status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subscription_status') border-red-500 @enderror">
                        <option value="">เลือกสถานะ</option>
                        <option value="trial" {{ old('subscription_status', $device->subscription_status) == 'trial' ? 'selected' : '' }}>🆓 Trial</option>
                        <option value="active" {{ old('subscription_status', $device->subscription_status) == 'active' ? 'selected' : '' }}>✅ Active</option>
                        <option value="expired" {{ old('subscription_status', $device->subscription_status) == 'expired' ? 'selected' : '' }}>⚠️ Expired</option>
                        <option value="suspended" {{ old('subscription_status', $device->subscription_status) == 'suspended' ? 'selected' : '' }}>🔒 Suspended</option>
                    </select>
                    @error('subscription_status')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ค่าบริการรายเดือน (บาท) <span class="text-red-600">*</span>
                    </label>
                    <input type="number" name="monthly_fee" value="{{ old('monthly_fee', $device->monthly_fee) }}" step="0.01" min="0" required
                        placeholder="0.00"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('monthly_fee') border-red-500 @enderror">
                    @error('monthly_fee')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        วันสิ้นสุดทดลองใช้
                    </label>
                    <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $device->trial_ends_at?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('trial_ends_at') border-red-500 @enderror">
                    @error('trial_ends_at')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        วันเริ่มแพคเกจ
                    </label>
                    <input type="date" name="subscription_starts_at" value="{{ old('subscription_starts_at', $device->subscription_starts_at?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subscription_starts_at') border-red-500 @enderror">
                    @error('subscription_starts_at')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        วันหมดอายุแพคเกจ
                    </label>
                    <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at', $device->subscription_ends_at?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subscription_ends_at') border-red-500 @enderror">
                    @error('subscription_ends_at')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @if($device->subscription_ends_at)
                    <p class="text-xs text-gray-500 mt-1">
                        ปัจจุบัน: {{ $device->subscription_ends_at->format('d/m/Y H:i') }}
                        @if($device->subscription_ends_at->isPast())
                            <span class="text-red-600">(หมดอายุแล้ว)</span>
                        @elseif($device->subscription_ends_at->diffInDays(now()) <= 7)
                            <span class="text-orange-600">(ใกล้หมดอายุ)</span>
                        @endif
                    </p>
                    @endif
                </div>

                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew', $device->auto_renew) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">ต่ออายุอัตโนมัติ</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ฟีเจอร์</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 {{ old('dual_screen_enabled', $device->dual_screen_enabled) ? 'bg-blue-50 border-blue-300' : '' }}">
                    <input type="checkbox" name="dual_screen_enabled" value="1" {{ old('dual_screen_enabled', $device->dual_screen_enabled) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Dual Screen (จอคู่)</span>
                        <p class="text-xs text-gray-500">รองรับการแสดงผลหน้าจอลูกค้า</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 {{ old('offline_mode_enabled', $device->offline_mode_enabled) ? 'bg-blue-50 border-blue-300' : '' }}">
                    <input type="checkbox" name="offline_mode_enabled" value="1" {{ old('offline_mode_enabled', $device->offline_mode_enabled) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Offline Mode</span>
                        <p class="text-xs text-gray-500">ทำงานแบบออฟไลน์ได้</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 {{ old('receipt_printer_enabled', $device->receipt_printer_enabled) ? 'bg-blue-50 border-blue-300' : '' }}">
                    <input type="checkbox" name="receipt_printer_enabled" value="1" {{ old('receipt_printer_enabled', $device->receipt_printer_enabled) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Receipt Printer</span>
                        <p class="text-xs text-gray-500">พิมพ์ใบเสร็จได้</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 {{ old('is_active', $device->is_active) ? 'bg-green-50 border-green-300' : '' }}">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $device->is_active) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">เปิดใช้งาน</span>
                        <p class="text-xs text-gray-500">อุปกรณ์สามารถใช้งานได้</p>
                    </span>
                </label>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <p>สร้างเมื่อ: {{ $device->created_at->format('d/m/Y H:i') }}</p>
                    <p>อัปเดตล่าสุด: {{ $device->updated_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.pos.devices.show', $device) }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        ยกเลิก
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        💾 บันทึกการแก้ไข
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@if($errors->any())
<script>
    alert('กรุณาตรวจสอบข้อมูลที่กรอก:\n\n{{ implode("\n", $errors->all()) }}');
</script>
@endif

@if(session('success'))
<script>
    alert('{{ session("success") }}');
</script>
@endif
@endsection
