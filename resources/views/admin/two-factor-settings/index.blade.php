@extends('layouts.admin')

@section('title', 'ตั้งค่า Two-Factor Authentication')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{ enabled: {{ $settings->enabled ? 'true' : 'false' }} }">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 via-pink-600 to-purple-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-shield-alt mr-3"></i>
                    ตั้งค่า Two-Factor Authentication (2FA)
                </h1>
                <p class="text-red-100">จัดการการยืนยันตัวตนแบบ 2 ชั้น เพื่อความปลอดภัยสูงสุด</p>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-90">สถานะระบบ</div>
                <div class="text-2xl font-bold" x-text="enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.two-factor-settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Main Toggle -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                        <i class="fas fa-power-off text-red-600 mr-2"></i>
                        เปิด/ปิด ระบบ 2FA
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">เปิดใช้งานการยืนยันตัวตนแบบ 2 ชั้นสำหรับระบบ</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" x-model="enabled"
                           {{ $settings->enabled ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-red-600"></div>
                </label>
            </div>
        </div>

        <!-- When to Require 2FA -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6" x-show="enabled">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-list-check text-indigo-600 mr-2"></i>
                ต้องการยืนยันตัวตนเมื่อไร
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                    <input type="checkbox" name="require_on_login" value="1"
                           {{ $settings->require_on_login ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-semibold text-gray-900 dark:text-white">เข้าสู่ระบบ</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันทุกครั้งที่เข้าสู่ระบบ</p>
                    </div>
                </label>

                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                    <input type="checkbox" name="require_on_withdrawal" value="1"
                           {{ $settings->require_on_withdrawal ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-semibold text-gray-900 dark:text-white">ถอนเงิน</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันก่อนถอนเงิน</p>
                    </div>
                </label>

                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                    <input type="checkbox" name="require_on_transfer" value="1"
                           {{ $settings->require_on_transfer ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-semibold text-gray-900 dark:text-white">โอนเงิน</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันก่อนโอนเงิน</p>
                    </div>
                </label>

                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                    <input type="checkbox" name="require_on_profile_change" value="1"
                           {{ $settings->require_on_profile_change ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-semibold text-gray-900 dark:text-white">แก้ไขโปรไฟล์</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันก่อนแก้ไขข้อมูล</p>
                    </div>
                </label>

                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                    <input type="checkbox" name="require_on_password_change" value="1"
                           {{ $settings->require_on_password_change ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-semibold text-gray-900 dark:text-white">เปลี่ยนรหัสผ่าน</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันก่อนเปลี่ยนรหัสผ่าน</p>
                    </div>
                </label>

                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                    <input type="checkbox" name="require_on_payment_method_change" value="1"
                           {{ $settings->require_on_payment_method_change ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <div class="ml-3">
                        <span class="font-semibold text-gray-900 dark:text-white">เปลี่ยนวิธีชำระเงิน</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันก่อนเปลี่ยนช่องทางชำระเงิน</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Available Methods -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6" x-show="enabled">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-mobile-alt text-green-600 mr-2"></i>
                วิธีการยืนยันตัวตน
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border-2 border-green-300 dark:border-green-700 rounded-xl p-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-sms text-4xl text-green-600"></i>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_sms" value="1"
                                   {{ $settings->allow_sms ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">SMS OTP</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ส่งรหัสผ่าน SMS</p>
                </div>

                <div class="border-2 border-blue-300 dark:border-blue-700 rounded-xl p-6 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fab fa-line text-4xl text-blue-600"></i>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_line" value="1"
                                   {{ $settings->allow_line ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">LINE OTP</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ส่งรหัสผ่าน LINE</p>
                </div>

                <div class="border-2 border-purple-300 dark:border-purple-700 rounded-xl p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-envelope text-4xl text-purple-600"></i>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_email" value="1"
                                   {{ $settings->allow_email ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Email OTP</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ส่งรหัสผ่าน Email</p>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วิธีการเริ่มต้น</label>
                <select name="default_method" class="w-full md:w-1/2 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="sms" {{ $settings->default_method === 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="line" {{ $settings->default_method === 'line' ? 'selected' : '' }}>LINE</option>
                    <option value="email" {{ $settings->default_method === 'email' ? 'selected' : '' }}>Email</option>
                </select>
            </div>
        </div>

        <!-- Advanced Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6" x-show="enabled">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-cogs text-yellow-600 mr-2"></i>
                การตั้งค่าขั้นสูง
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-clock mr-1"></i> Grace Period (นาที)
                    </label>
                    <input type="number" name="grace_period_minutes" value="{{ $settings->grace_period_minutes }}" min="0" max="120"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ระยะเวลาที่ไม่ต้องยืนยันซ้ำหลังจากยืนยันครั้งแรก</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i> จำอุปกรณ์ (วัน)
                    </label>
                    <input type="number" name="remember_device_days" value="{{ $settings->remember_device_days }}" min="1" max="365"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนวันที่จดจำอุปกรณ์ที่เชื่อถือได้</p>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <input type="checkbox" name="allow_remember_device" value="1"
                               {{ $settings->allow_remember_device ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="font-semibold text-gray-900 dark:text-white">อนุญาตให้จดจำอุปกรณ์</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ผู้ใช้สามารถเลือกจดจำอุปกรณ์เพื่อไม่ต้องยืนยันทุกครั้ง</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span class="text-sm">การเปลี่ยนแปลงจะมีผลทันที</span>
                </div>
                <button type="submit"
                        class="bg-gradient-to-r from-red-600 to-pink-600 text-white px-8 py-3 rounded-lg font-semibold hover:from-red-700 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    gsap.from('.space-y-6 > div, .space-y-6 > form > div', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        ease: 'power2.out'
    });
</script>
@endpush
@endsection
