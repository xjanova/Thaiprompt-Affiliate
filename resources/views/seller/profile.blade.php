@extends('layouts.seller')

@section('title', 'โปรไฟล์')

@section('content')
{{--
/**
 * Seller Profile Page - จัดการโปรไฟล์ผู้ขาย
 *
 * รองรับ:
 * - แสดงและแก้ไขข้อมูลส่วนตัว
 * - อัพโหลด avatar (แปลงเป็น WebP อัตโนมัติ)
 * - เปลี่ยนรหัสผ่าน
 * - Dark mode support
 *
 * @version 2.0.0
 */
--}}

<div class="space-y-6 pb-24" x-data="sellerProfileManager()">
    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" x-ref="profileForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Sidebar - Avatar Card --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    {{-- Avatar Section --}}
                    <div class="text-center mb-6">
                        <div class="relative inline-block group">
                            {{-- Avatar with Glow Effect --}}
                            <div class="relative w-32 h-32 mx-auto mb-4">
                                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 rounded-full blur-lg opacity-60 group-hover:opacity-80 transition"></div>
                                <div class="relative w-full h-full bg-gradient-to-br from-cyan-500 to-blue-500 rounded-full p-1 shadow-2xl">
                                    <div class="w-full h-full bg-white dark:bg-gray-800 rounded-full p-1">
                                        <img :src="avatarPreview || '{{ $user->profile_picture_url }}'"
                                             alt="{{ $user->name }}"
                                             class="w-full h-full object-cover rounded-full ring-4 ring-white dark:ring-gray-700">
                                    </div>
                                </div>
                            </div>

                            {{-- Upload Button --}}
                            <label for="avatar-upload"
                                   class="block w-full px-4 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer text-center">
                                <i class="fas fa-camera mr-2"></i>เปลี่ยนรูปโปรไฟล์
                            </label>

                            <input type="file"
                                   id="avatar-upload"
                                   name="profile_picture"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   class="hidden"
                                   @change="handleAvatarChange($event)">

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                JPG, PNG, GIF หรือ WebP<br>
                                ขนาดไม่เกิน 5MB<br>
                                <span class="text-cyan-600 dark:text-cyan-400 font-semibold">จะแปลงเป็น WebP อัตโนมัติ</span>
                            </p>
                        </div>

                        {{-- User Info --}}
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                            <div class="mt-3 inline-flex items-center px-3 py-1 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-lg text-xs font-bold shadow-lg">
                                <i class="fas fa-store mr-2"></i>ผู้ขาย
                            </div>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-gray-700 dark:text-gray-300">
                            <span class="text-sm">
                                <i class="fas fa-calendar mr-2 text-cyan-500"></i>
                                สมัครเมื่อ
                            </span>
                            <span class="text-sm font-medium">
                                {{ $user->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Content - Profile Forms --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Personal Information --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-cyan-500/10 to-blue-500/10">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            ข้อมูลส่วนตัว
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Name --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-signature mr-1 text-cyan-600"></i>ชื่อ-นามสกุล <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       value="{{ old('name', $user->name) }}"
                                       required
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition text-gray-900 dark:text-white">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-envelope mr-1 text-cyan-600"></i>อีเมล <span class="text-red-500">*</span>
                                </label>
                                <input type="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition text-gray-900 dark:text-white">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-phone mr-1 text-cyan-600"></i>เบอร์โทรศัพท์
                                </label>
                                <input type="tel"
                                       name="phone"
                                       value="{{ old('phone', $user->phone) }}"
                                       placeholder="0812345678"
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/20 transition text-gray-900 dark:text-white">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Password Change --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-red-500/10 to-pink-500/10">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-key text-white"></i>
                            </div>
                            เปลี่ยนรหัสผ่าน
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Current Password --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-lock mr-1 text-red-600"></i>รหัสผ่านปัจจุบัน
                            </label>
                            <div class="relative" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="current_password"
                                       placeholder="ใส่รหัสผ่านปัจจุบัน (ถ้าต้องการเปลี่ยน)"
                                       class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition text-gray-900 dark:text-white">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- New Password --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-key mr-1 text-red-600"></i>รหัสผ่านใหม่
                            </label>
                            <div class="relative" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="new_password"
                                       placeholder="ใส่รหัสผ่านใหม่"
                                       class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition text-gray-900 dark:text-white">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-check-circle mr-1 text-red-600"></i>ยืนยันรหัสผ่านใหม่
                            </label>
                            <div class="relative" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="new_password_confirmation"
                                       placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง"
                                       class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition text-gray-900 dark:text-white">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <i class="fas fa-info-circle mr-2"></i>
                                รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end gap-4">
                    <a href="{{ route('seller.dashboard') }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transition-all">
                        <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
/**
 * Seller Profile Manager - Alpine.js Component
 */
function sellerProfileManager() {
    return {
        avatarPreview: null,

        /**
         * จัดการเมื่อเลือกไฟล์ avatar ใหม่
         */
        handleAvatarChange(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            // ตรวจสอบประเภทไฟล์
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, GIF, WebP) เท่านั้น');
                event.target.value = '';
                return;
            }

            // ตรวจสอบขนาดไฟล์ (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                event.target.value = '';
                return;
            }

            // แสดง preview
            const reader = new FileReader();
            reader.onload = (e) => {
                this.avatarPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };
}
</script>
@endpush
@endsection
