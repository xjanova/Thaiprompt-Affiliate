@extends('layouts.user')

@section('title', 'จัดการโปรไฟล์')

@section('content')
{{--
/**
 * User Profile Management - Arrow X Theme
 *
 * หน้าจัดการโปรไฟล์ผู้ใช้แบบ Arrow X Theme พร้อม:
 * - Avatar Upload with Crop
 * - Profile Information Edit
 * - Contact Information
 * - Address Management
 * - Password Change
 * - Dark Mode Support
 *
 * @version 3.0.0
 * @theme Arrow X
 */
--}}

<div class="space-y-6 pb-20 lg:pb-6" x-data="profileManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="glass-fusion-card rounded-2xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent dark:from-purple-400 dark:via-pink-400 dark:to-orange-400">
                    จัดการโปรไฟล์
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    แก้ไขข้อมูลส่วนตัวและการตั้งค่าของคุณ
                </p>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="glass-fusion-card rounded-xl p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl mr-3"></i>
                    <span class="font-semibold text-green-800 dark:text-green-200">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="glass-fusion-card rounded-xl p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-l-4 border-red-500" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-xl mr-3"></i>
                    <span class="font-semibold text-red-800 dark:text-red-200">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Sidebar - Profile Card --}}
        <div class="lg:col-span-1">
            <div class="glass-fusion-card rounded-2xl p-6 shadow-lg sticky top-6">
                {{-- Avatar Section --}}
                <div class="text-center mb-6">
                    <form action="{{ route('user.profile.avatar.update') }}" method="POST" enctype="multipart/form-data" x-ref="avatarForm">
                        @csrf
                        @method('PUT')

                        <div class="relative inline-block group">
                            {{-- Avatar Preview --}}
                            <div class="relative w-32 h-32 mx-auto">
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full p-1 shadow-2xl animate-spin-slow">
                                    <div class="w-full h-full bg-white dark:bg-gray-800 rounded-full p-1">
                                        <img :src="avatarPreview || '{{ $user->profile_picture_url }}'"
                                             alt="{{ $user->name }}"
                                             class="w-full h-full object-cover rounded-full ring-4 ring-white dark:ring-gray-700">
                                    </div>
                                </div>
                            </div>

                            {{-- Upload Overlay --}}
                            <label for="avatar-upload"
                                   class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                <div class="text-center text-white">
                                    <i class="fas fa-camera text-2xl mb-1"></i>
                                    <p class="text-xs font-semibold">เปลี่ยนรูป</p>
                                </div>
                            </label>

                            <input type="file"
                                   id="avatar-upload"
                                   name="avatar"
                                   accept="image/*"
                                   class="hidden"
                                   @change="handleAvatarChange($event)">
                        </div>

                        {{-- Upload Buttons --}}
                        <div x-show="avatarFile" x-transition class="mt-4 flex gap-2 justify-center">
                            <button type="submit"
                                    class="px-4 py-2 bg-gradient-to-r from-green-500 to-teal-500 text-white rounded-lg font-semibold hover:from-green-600 hover:to-teal-600 transition">
                                <i class="fas fa-check mr-2"></i>บันทึก
                            </button>
                            <button type="button"
                                    @click="clearAvatar()"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                <i class="fas fa-times mr-2"></i>ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>

                {{-- User Info --}}
                <div class="text-center border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $user->name }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        {{ $user->email }}
                    </p>

                    @if($user->member_number)
                        <div class="inline-flex items-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                            <i class="fas fa-id-card text-purple-600 dark:text-purple-400 text-sm mr-2"></i>
                            <span class="text-xs font-mono font-semibold text-purple-900 dark:text-purple-300">
                                {{ $user->member_number }}
                            </span>
                        </div>
                    @endif

                    {{-- Quick Stats --}}
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($user->rank_points ?? 0) }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">คะแนน</div>
                        </div>
                        <div class="text-center p-3 bg-gradient-to-br from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->currentRank->level ?? 0 }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Rank</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Content - Forms --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Personal Information --}}
            <div class="glass-fusion-card rounded-2xl p-6 shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user text-purple-500 mr-3"></i>
                        ข้อมูลส่วนตัว
                    </h2>
                </div>

                <form action="{{ route('user.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-user text-purple-500 mr-1"></i>
                                ชื่อ-นามสกุล
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                          dark:bg-gray-800 dark:text-white transition-all
                                          @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email (Read-only) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-envelope text-purple-500 mr-1"></i>
                                อีเมล
                            </label>
                            <input type="email"
                                   value="{{ $user->email }}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                                   disabled>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">อีเมลไม่สามารถแก้ไขได้</p>
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-phone text-purple-500 mr-1"></i>
                                เบอร์โทรศัพท์
                            </label>
                            <input type="tel"
                                   name="phone"
                                   value="{{ old('phone', $user->phone) }}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                          dark:bg-gray-800 dark:text-white transition-all
                                          @error('phone') border-red-500 @enderror"
                                   placeholder="08X-XXX-XXXX">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Date of Birth --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-birthday-cake text-purple-500 mr-1"></i>
                                วันเกิด
                            </label>
                            <input type="date"
                                   name="date_of_birth"
                                   value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                          dark:bg-gray-800 dark:text-white transition-all">
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-map-marker-alt text-purple-500 mr-1"></i>
                            ที่อยู่
                        </label>
                        <textarea name="address"
                                  rows="3"
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                         focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                         dark:bg-gray-800 dark:text-white transition-all"
                                  placeholder="บ้านเลขที่, ถนน, ตำบล/แขวง">{{ old('address', $user->address) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- City --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                อำเภอ/เขต
                            </label>
                            <input type="text"
                                   name="city"
                                   value="{{ old('city', $user->city) }}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                          dark:bg-gray-800 dark:text-white transition-all">
                        </div>

                        {{-- State --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                จังหวัด
                            </label>
                            <input type="text"
                                   name="state"
                                   value="{{ old('state', $user->state) }}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                          dark:bg-gray-800 dark:text-white transition-all">
                        </div>

                        {{-- Postal Code --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                รหัสไปรษณีย์
                            </label>
                            <input type="text"
                                   name="postal_code"
                                   value="{{ old('postal_code', $user->postal_code) }}"
                                   maxlength="5"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                          dark:bg-gray-800 dark:text-white transition-all"
                                   placeholder="XXXXX">
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-end pt-4">
                        <button type="submit"
                                class="btn-shimmer px-6 py-3 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                                       hover:from-purple-700 hover:via-pink-700 hover:to-orange-700
                                       text-white font-bold rounded-xl shadow-lg hover:shadow-xl
                                       transform hover:-translate-y-1 transition-all duration-300">
                                    <i class="fas fa-save mr-2"></i>
                            บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="glass-fusion-card rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-lock text-orange-500 mr-3"></i>
                    เปลี่ยนรหัสผ่าน
                </h2>

                <form action="{{ route('user.profile.password.update') }}" method="POST" class="space-y-4" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                    @csrf
                    @method('PUT')

                    {{-- Current Password --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            รหัสผ่านปัจจุบัน
                        </label>
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'"
                                   name="current_password"
                                   class="w-full px-4 py-3 pr-12 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-orange-500 focus:border-orange-500
                                          dark:bg-gray-800 dark:text-white transition-all
                                          @error('current_password') border-red-500 @enderror">
                            <button type="button"
                                    @click="showCurrent = !showCurrent"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <i :class="showCurrent ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- New Password --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            รหัสผ่านใหม่
                        </label>
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'"
                                   name="password"
                                   class="w-full px-4 py-3 pr-12 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-orange-500 focus:border-orange-500
                                          dark:bg-gray-800 dark:text-white transition-all
                                          @error('password') border-red-500 @enderror">
                            <button type="button"
                                    @click="showNew = !showNew"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ยืนยันรหัสผ่านใหม่
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'"
                                   name="password_confirmation"
                                   class="w-full px-4 py-3 pr-12 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                          focus:ring-2 focus:ring-orange-500 focus:border-orange-500
                                          dark:bg-gray-800 dark:text-white transition-all">
                            <button type="button"
                                    @click="showConfirm = !showConfirm"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-end pt-4">
                        <button type="submit"
                                class="btn-shimmer px-6 py-3 bg-gradient-to-r from-orange-600 via-red-600 to-pink-600
                                       hover:from-orange-700 hover:via-red-700 hover:to-pink-700
                                       text-white font-bold rounded-xl shadow-lg hover:shadow-xl
                                       transform hover:-translate-y-1 transition-all duration-300">
                            <i class="fas fa-key mr-2"></i>
                            เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Profile Manager Alpine.js Component
 *
 * จัดการ state และ logic สำหรับหน้า Profile
 */
function profileManager() {
    return {
        avatarPreview: null,
        avatarFile: null,

        /**
         * เริ่มต้น component
         */
        init() {
            // Initialization logic
        },

        /**
         * จัดการการเปลี่ยนรูป Avatar
         */
        handleAvatarChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
                event.target.value = '';
                return;
            }

            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                event.target.value = '';
                return;
            }

            this.avatarFile = file;

            // Create preview
            const reader = new FileReader();
            reader.onload = (e) => {
                this.avatarPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        /**
         * ล้างการเลือกรูป Avatar
         */
        clearAvatar() {
            this.avatarPreview = null;
            this.avatarFile = null;
            const input = document.getElementById('avatar-upload');
            if (input) input.value = '';
        }
    };
}
</script>
@endpush

@push('styles')
<style>
/**
 * Arrow X Profile Styles
 */

/* Button Shimmer Effect */
.btn-shimmer {
    position: relative;
    overflow: hidden;
}

.btn-shimmer::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    transition: left 0.5s ease;
}

.btn-shimmer:hover::before {
    left: 100%;
}

/* Spin Slow Animation */
@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}

/* Glass Fusion Card */
.glass-fusion-card {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.25) 0%,
        rgba(255, 255, 255, 0.15) 100%
    );
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.dark .glass-fusion-card {
    background: linear-gradient(
        135deg,
        rgba(31, 41, 55, 0.8) 0%,
        rgba(17, 24, 39, 0.7) 100%
    );
    border-color: rgba(75, 85, 99, 0.5);
}
</style>
@endpush
@endsection
