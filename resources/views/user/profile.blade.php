@extends('layouts.user-arrow-x')

@section('title', 'จัดการโปรไฟล์')

@section('content')
{{--
/**
 * User Profile Management - Arrow X Theme V3
 *
 * หน้าจัดการโปรไฟล์ผู้ใช้แบบ Arrow X Theme พร้อม:
 * - Avatar Upload with Live Preview (WebP)
 * - Profile Information Edit
 * - Contact Information
 * - Address Management
 * - Shipping Address (NEW)
 * - Password Change
 * - Floating Save Button (NEW)
 * - Dark Mode Support
 *
 * @version 3.1.0
 * @theme Arrow X
 */
--}}

<div class="space-y-6 pb-24" x-data="profileManager()" x-init="init()">
    {{-- Page Header - ใช้ Arrow X Card --}}
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent dark:from-purple-400 dark:via-pink-400 dark:to-orange-400">
                    <i class="fas fa-user-edit mr-2"></i>จัดการโปรไฟล์
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    แก้ไขข้อมูลส่วนตัวและการตั้งค่าของคุณ
                </p>
            </div>
        </div>
    </x-arrow-x.card-v3>

    {{-- Success/Error Messages - ใช้ Arrow X Alert --}}
    @if(session('success'))
        <x-arrow-x.alert-v3 type="success" :dismissible="true">
            {{ session('success') }}
        </x-arrow-x.alert-v3>
    @endif

    @if(session('error'))
        <x-arrow-x.alert-v3 type="danger" :dismissible="true">
            {{ session('error') }}
        </x-arrow-x.alert-v3>
    @endif

    {{-- Main Profile Form --}}
    <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data" x-ref="profileForm" @submit="formChanged = false">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Sidebar - Avatar Card - ใช้ Arrow X Card --}}
            <div class="lg:col-span-1">
                <x-arrow-x.card-v3 class="p-6 sticky top-6">
                    {{-- Avatar Section --}}
                    <div class="text-center mb-6">
                        <div class="relative inline-block group">
                            {{-- Avatar Preview with Glow --}}
                            <div class="relative w-40 h-40 mx-auto mb-4">
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-full blur-lg opacity-60 group-hover:opacity-80 transition"></div>
                                <div class="relative w-full h-full bg-gradient-to-br from-purple-500 to-pink-500 rounded-full p-1 shadow-2xl">
                                    <div class="w-full h-full bg-white dark:bg-gray-800 rounded-full p-1">
                                        <img :src="avatarPreview || '{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('images/default-avatar.png') }}'"
                                             alt="{{ $user->name }}"
                                             class="w-full h-full object-cover rounded-full ring-4 ring-white dark:ring-gray-700">
                                    </div>
                                </div>
                            </div>

                            {{-- Upload Button --}}
                            <label for="avatar-upload"
                                   class="block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform  transition-all duration-300 cursor-pointer text-center">
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
                                <span class="text-purple-600 dark:text-purple-400 font-semibold">จะแปลงเป็น WebP อัตโนมัติ</span>
                            </p>
                        </div>

                        {{-- User Info --}}
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                            @if($user->member_number)
                                <div class="mt-3 inline-flex items-center px-3 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg text-xs font-bold shadow-lg">
                                    <i class="fas fa-id-card mr-2"></i>{{ $user->member_number }}
                                </div>
                            @endif
                        </div>
                    </div>
                </x-arrow-x.card-v3>
            </div>

            {{-- Right Content - Profile Forms --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Personal Information - ใช้ Arrow X Card --}}
                <x-arrow-x.card-v3 class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        ข้อมูลส่วนตัว
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-signature mr-1 text-purple-600"></i>ชื่อ-นามสกุล <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required
                                   class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                                   @input="formChanged = true">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-envelope mr-1 text-purple-600"></i>อีเมล <span class="text-red-500">*</span>
                            </label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   required
                                   class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                                   @input="formChanged = true">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-phone mr-1 text-purple-600"></i>เบอร์โทรศัพท์
                            </label>
                            <input type="tel"
                                   name="phone"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="0812345678"
                                   class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                                   @input="formChanged = true">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Birth Date --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-birthday-cake mr-1 text-purple-600"></i>วันเกิด
                            </label>
                            <input type="date"
                                   name="date_of_birth"
                                   value="{{ old('date_of_birth', $user->date_of_birth) }}"
                                   class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                                   @input="formChanged = true">
                            @error('date_of_birth')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Gender --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-venus-mars mr-1 text-purple-600"></i>เพศ
                            </label>
                            <select name="gender"
                                    class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                                    @change="formChanged = true">
                                <option value="">เลือกเพศ</option>
                                <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>ชาย</option>
                                <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>หญิง</option>
                                <option value="other" {{ old('gender', $user->gender) === 'other' ? 'selected' : '' }}>อื่นๆ</option>
                                <option value="prefer_not_to_say" {{ old('gender', $user->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>ไม่ระบุ</option>
                            </select>
                        </div>
                    </div>
                </x-arrow-x.card-v3>

                {{-- Billing Address --}}
                <x-arrow-x.card-v3 class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-map-marker-alt text-white"></i>
                        </div>
                        ที่อยู่ (สำหรับออกใบเสร็จ)
                    </h2>

                    <div class="grid grid-cols-1 gap-4">
                        {{-- Address --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-home mr-1 text-green-600"></i>ที่อยู่
                            </label>
                            <textarea name="address"
                                      rows="2"
                                      placeholder="บ้านเลขที่ ถนน"
                                      class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition resize-none"
                                      @input="formChanged = true">{{ old('address', $user->address) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- City --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-city mr-1 text-green-600"></i>เขต/อำเภอ
                                </label>
                                <input type="text"
                                       name="city"
                                       value="{{ old('city', $user->city) }}"
                                       placeholder="เขต/อำเภอ"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition"
                                       @input="formChanged = true">
                            </div>

                            {{-- State --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-map mr-1 text-green-600"></i>จังหวัด
                                </label>
                                <input type="text"
                                       name="state"
                                       value="{{ old('state', $user->state) }}"
                                       placeholder="จังหวัด"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition"
                                       @input="formChanged = true">
                            </div>

                            {{-- Postal Code --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-mail-bulk mr-1 text-green-600"></i>รหัสไปรษณีย์
                                </label>
                                <input type="text"
                                       name="postal_code"
                                       value="{{ old('postal_code', $user->postal_code) }}"
                                       placeholder="10100"
                                       maxlength="5"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition"
                                       @input="formChanged = true">
                            </div>

                            {{-- Country --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-globe mr-1 text-green-600"></i>ประเทศ
                                </label>
                                <select name="country"
                                        class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition"
                                        @change="formChanged = true">
                                    <option value="TH" {{ old('country', $user->country ?? 'TH') === 'TH' ? 'selected' : '' }}>ประเทศไทย</option>
                                    <option value="US" {{ old('country', $user->country) === 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="GB" {{ old('country', $user->country) === 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </x-arrow-x.card-v3>

                {{-- Shipping Address --}}
                <x-arrow-x.card-v3 class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-shipping-fast text-white"></i>
                            </div>
                            ที่อยู่จัดส่ง
                        </h2>

                        {{-- Copy from Billing Address --}}
                        <button type="button"
                                @click="copyBillingAddress()"
                                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transform  transition-all">
                            <i class="fas fa-copy mr-2"></i>คัดลอกจากที่อยู่หลัก
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        {{-- Shipping Address --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-home mr-1 text-orange-600"></i>ที่อยู่จัดส่ง
                            </label>
                            <textarea name="shipping_address"
                                      rows="2"
                                      placeholder="บ้านเลขที่ ถนน"
                                      class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition resize-none"
                                      x-ref="shippingAddress"
                                      @input="formChanged = true">{{ old('shipping_address', $user->shipping_address) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Shipping City --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-city mr-1 text-orange-600"></i>เขต/อำเภอ
                                </label>
                                <input type="text"
                                       name="shipping_city"
                                       value="{{ old('shipping_city', $user->shipping_city) }}"
                                       placeholder="เขต/อำเภอ"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition"
                                       x-ref="shippingCity"
                                       @input="formChanged = true">
                            </div>

                            {{-- Shipping State --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-map mr-1 text-orange-600"></i>จังหวัด
                                </label>
                                <input type="text"
                                       name="shipping_state"
                                       value="{{ old('shipping_state', $user->shipping_state) }}"
                                       placeholder="จังหวัด"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition"
                                       x-ref="shippingState"
                                       @input="formChanged = true">
                            </div>

                            {{-- Shipping Postal Code --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-mail-bulk mr-1 text-orange-600"></i>รหัสไปรษณีย์
                                </label>
                                <input type="text"
                                       name="shipping_postal_code"
                                       value="{{ old('shipping_postal_code', $user->shipping_postal_code) }}"
                                       placeholder="10100"
                                       maxlength="5"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition"
                                       x-ref="shippingPostalCode"
                                       @input="formChanged = true">
                            </div>

                            {{-- Shipping Country --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-globe mr-1 text-orange-600"></i>ประเทศ
                                </label>
                                <select name="shipping_country"
                                        class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition"
                                        x-ref="shippingCountry"
                                        @change="formChanged = true">
                                    <option value="TH" {{ old('shipping_country', $user->shipping_country ?? 'TH') === 'TH' ? 'selected' : '' }}>ประเทศไทย</option>
                                    <option value="US" {{ old('shipping_country', $user->shipping_country) === 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="GB" {{ old('shipping_country', $user->shipping_country) === 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                </select>
                            </div>

                            {{-- Shipping Phone --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-phone mr-1 text-orange-600"></i>เบอร์โทรติดต่อ (จัดส่ง)
                                </label>
                                <input type="tel"
                                       name="shipping_phone"
                                       value="{{ old('shipping_phone', $user->shipping_phone) }}"
                                       placeholder="0812345678"
                                       class="input-3d w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition"
                                       x-ref="shippingPhone"
                                       @input="formChanged = true">
                            </div>
                        </div>
                    </div>
                </x-arrow-x.card-v3>

                {{-- Password Change --}}
                <x-arrow-x.card-v3 class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-key text-white"></i>
                        </div>
                        เปลี่ยนรหัสผ่าน
                    </h2>

                    <div class="grid grid-cols-1 gap-4">
                        {{-- Current Password --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-lock mr-1 text-red-600"></i>รหัสผ่านปัจจุบัน
                            </label>
                            <div class="relative" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="current_password"
                                       placeholder="ใส่รหัสผ่านปัจจุบัน (ถ้าต้องการเปลี่ยน)"
                                       class="input-3d w-full px-4 py-3 pr-12 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition">
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
                                       class="input-3d w-full px-4 py-3 pr-12 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition">
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
                                       class="input-3d w-full px-4 py-3 pr-12 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition">
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
                </x-arrow-x.card-v3>
            </div>
        </div>
    </form>

    {{-- Floating Save Button --}}
    <div x-show="formChanged"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50"
         style="display: none;">
        <div class="glass-fusion-card rounded-full shadow-2xl p-2 flex items-center gap-4 border-2 border-purple-500/50">
            <button type="button"
                    @click="$refs.profileForm.submit()"
                    class="px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-lg rounded-full shadow-2xl hover:shadow-3xl transition-transform hover:scale-[1.02] transition-all duration-300 flex items-center gap-3">
                <i class="fas fa-save text-2xl"></i>
                <span>บันทึกการเปลี่ยนแปลง</span>
            </button>
            <button type="button"
                    @click="formChanged = false; window.location.reload()"
                    class="px-6 py-4 bg-white/20 hover:bg-white/30 dark:bg-gray-800/50 dark:hover:bg-gray-800/70 text-gray-700 dark:text-gray-300 font-semibold rounded-full transition-all">
                <i class="fas fa-times mr-2"></i>ยกเลิก
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
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

/* Input 3D Effect */
.input-3d {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.input-3d:focus {
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2), 0 0 0 3px rgba(139, 92, 246, 0.1);
    transform: translateY(-1px);
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
</style>
@endpush

@push('scripts')
<script>
/**
 * Profile Manager - Alpine.js Component
 */
function profileManager() {
    return {
        avatarPreview: null,
        formChanged: false,

        init() {
            // Track form changes
            const form = this.$refs.profileForm;
            if (form) {
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('input', () => {
                        this.formChanged = true;
                    });
                });
            }
        },

        /**
         * Handle Avatar Upload and Preview
         */
        handleAvatarChange(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, GIF, WebP) เท่านั้น');
                event.target.value = '';
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                event.target.value = '';
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                this.avatarPreview = e.target.result;
                this.formChanged = true;
            };
            reader.readAsDataURL(file);
        },

        /**
         * Copy Billing Address to Shipping Address
         */
        copyBillingAddress() {
            // Get billing address values
            const address = document.querySelector('textarea[name="address"]').value;
            const city = document.querySelector('input[name="city"]').value;
            const state = document.querySelector('input[name="state"]').value;
            const postalCode = document.querySelector('input[name="postal_code"]').value;
            const country = document.querySelector('select[name="country"]').value;
            const phone = document.querySelector('input[name="phone"]').value;

            // Set shipping address values
            this.$refs.shippingAddress.value = address;
            this.$refs.shippingCity.value = city;
            this.$refs.shippingState.value = state;
            this.$refs.shippingPostalCode.value = postalCode;
            this.$refs.shippingCountry.value = country;
            this.$refs.shippingPhone.value = phone;

            this.formChanged = true;

            // Show success message
            this.showNotification('คัดลอกที่อยู่สำเร็จ!', 'success');
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'success') {
            // Simple alert for now (can be enhanced with toast notification)
            alert(message);
        }
    };
}
</script>
@endpush
@endsection
