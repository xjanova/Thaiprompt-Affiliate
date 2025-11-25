{{--
    หน้าตั้งค่าของผู้ให้บริการ
    จัดการการแจ้งเตือน, การรับงาน และข้อมูลบัญชี
--}}
@extends('layouts.app')

@section('title', $pageTitle ?? 'ตั้งค่า Provider')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
            <i class="fas fa-cog mr-3"></i>{{ $pageTitle ?? 'ตั้งค่า Provider' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการการตั้งค่าบัญชีผู้ให้บริการของคุณ</p>
    </div>

    <form action="{{ route('provider.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Notification Settings --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-bell mr-2 text-yellow-500"></i>การแจ้งเตือน
            </h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แจ้งเตือนทางอีเมล</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รับการแจ้งเตือนงานใหม่ทางอีเมล</p>
                    </div>
                    <input type="hidden" name="notification_email" value="0">
                    <input type="checkbox" name="notification_email" value="1" {{ old('notification_email', $provider->notification_email ?? false) ? 'checked' : '' }} class="w-5 h-5 text-purple-600 rounded">
                </label>
                <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แจ้งเตือนทาง LINE</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รับการแจ้งเตือนงานใหม่ทาง LINE</p>
                    </div>
                    <input type="hidden" name="notification_line" value="0">
                    <input type="checkbox" name="notification_line" value="1" {{ old('notification_line', $provider->notification_line ?? false) ? 'checked' : '' }} class="w-5 h-5 text-purple-600 rounded">
                </label>
                <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แจ้งเตือนทาง SMS</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รับการแจ้งเตือนงานใหม่ทาง SMS</p>
                    </div>
                    <input type="hidden" name="notification_sms" value="0">
                    <input type="checkbox" name="notification_sms" value="1" {{ old('notification_sms', $provider->notification_sms ?? false) ? 'checked' : '' }} class="w-5 h-5 text-purple-600 rounded">
                </label>
            </div>
        </div>

        {{-- Work Settings --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-briefcase mr-2 text-blue-500"></i>การรับงาน
            </h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">รับงานอัตโนมัติ</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ระบบจะรับงานให้อัตโนมัติเมื่อมีงานเข้ามา</p>
                    </div>
                    <input type="hidden" name="auto_accept" value="0">
                    <input type="checkbox" name="auto_accept" value="1" {{ old('auto_accept', $provider->auto_accept ?? false) ? 'checked' : '' }} class="w-5 h-5 text-purple-600 rounded">
                </label>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">ระยะทางสูงสุดที่รับงาน (km)</label>
                    <input type="number" name="max_distance_km" value="{{ old('max_distance_km', $provider->max_distance_km ?? 50) }}" min="1" max="500" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">งานที่อยู่เกินระยะทางนี้จะไม่ถูกเสนอให้คุณ</p>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">ช่วงเวลาที่พร้อมรับงาน</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">เริ่มต้น</label>
                            <input type="time" name="available_from" value="{{ old('available_from', $provider->available_from ?? '08:00') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">สิ้นสุด</label>
                            <input type="time" name="available_to" value="{{ old('available_to', $provider->available_to ?? '20:00') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Service Areas --}}
        @if(isset($areas) && $areas->count() > 0)
            <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
                <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                    <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>พื้นที่ให้บริการ
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($areas as $area)
                        <label class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <input type="checkbox" name="service_areas[]" value="{{ $area->id }}" {{ in_array($area->id, $provider->service_areas ?? []) ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded mr-2">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $area->province }} {{ $area->district ? '(' . $area->district . ')' : '' }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Service Categories --}}
        @if(isset($categories) && $categories->count() > 0)
            <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
                <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                    <i class="fas fa-tags mr-2 text-green-500"></i>ประเภทบริการที่รับ
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($categories as $category)
                        <label class="flex items-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <input type="checkbox" name="service_categories[]" value="{{ $category->id }}" {{ in_array($category->id, $provider->service_categories ?? []) ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded mr-2">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bank Account --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-university mr-2 text-indigo-500"></i>ข้อมูลบัญชีธนาคาร
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ธนาคาร</label>
                    <select name="bank_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">-- เลือกธนาคาร --</option>
                        <option value="กสิกรไทย" {{ old('bank_name', $provider->bank_name) == 'กสิกรไทย' ? 'selected' : '' }}>กสิกรไทย</option>
                        <option value="ไทยพาณิชย์" {{ old('bank_name', $provider->bank_name) == 'ไทยพาณิชย์' ? 'selected' : '' }}>ไทยพาณิชย์</option>
                        <option value="กรุงเทพ" {{ old('bank_name', $provider->bank_name) == 'กรุงเทพ' ? 'selected' : '' }}>กรุงเทพ</option>
                        <option value="กรุงไทย" {{ old('bank_name', $provider->bank_name) == 'กรุงไทย' ? 'selected' : '' }}>กรุงไทย</option>
                        <option value="ทหารไทยธนชาต" {{ old('bank_name', $provider->bank_name) == 'ทหารไทยธนชาต' ? 'selected' : '' }}>ทหารไทยธนชาต</option>
                        <option value="กรุงศรีอยุธยา" {{ old('bank_name', $provider->bank_name) == 'กรุงศรีอยุธยา' ? 'selected' : '' }}>กรุงศรีอยุธยา</option>
                        <option value="ออมสิน" {{ old('bank_name', $provider->bank_name) == 'ออมสิน' ? 'selected' : '' }}>ออมสิน</option>
                        <option value="ธ.ก.ส." {{ old('bank_name', $provider->bank_name) == 'ธ.ก.ส.' ? 'selected' : '' }}>ธ.ก.ส.</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขบัญชี</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $provider->bank_account_number) }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white font-mono" placeholder="xxx-x-xxxxx-x">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบัญชี</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $provider->bank_account_name) }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-4">
            <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-lg transition font-medium">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
