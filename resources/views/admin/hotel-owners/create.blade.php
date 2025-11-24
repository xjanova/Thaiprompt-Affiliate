@extends('layouts.admin-v3')

@section('title', 'เพิ่มเจ้าของโรงแรมใหม่')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header with Back Button --}}
        <div class="mb-6">
            <a href="{{ route('admin.hotel-owners.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl shadow-lg transition-all border border-gray-200 dark:border-gray-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปรายการเจ้าของโรงแรม
            </a>
        </div>

        {{-- Form Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header Gradient --}}
            <div class="relative h-32 bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 p-8 flex items-center">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">เพิ่มเจ้าของโรงแรมใหม่</h1>
                        <p class="text-purple-100">กรอกข้อมูลเจ้าของโรงแรมและกำหนดสิทธิ์การเข้าถึง</p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.hotel-owners.store') }}" class="p-8">
                @csrf

                <div class="space-y-6">
                    {{-- Personal Information Section --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            ข้อมูลส่วนตัว
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Name --}}
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ชื่อ-นามสกุล <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       required
                                       value="{{ old('name') }}"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all @error('name') border-red-500 @enderror"
                                       placeholder="กรอกชื่อ-นามสกุลเจ้าของโรงแรม">
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    อีเมล <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input type="email"
                                           name="email"
                                           id="email"
                                           required
                                           value="{{ old('email') }}"
                                           class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all @error('email') border-red-500 @enderror"
                                           placeholder="example@hotel.com">
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    เบอร์โทรศัพท์
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <input type="text"
                                           name="phone"
                                           id="phone"
                                           value="{{ old('phone') }}"
                                           class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all @error('phone') border-red-500 @enderror"
                                           placeholder="08X-XXX-XXXX">
                                </div>
                                @error('phone')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Security Section --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            ตั้งค่ารหัสผ่าน
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Password --}}
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    รหัสผ่าน <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </div>
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           required
                                           class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all @error('password') border-red-500 @enderror"
                                           placeholder="อย่างน้อย 8 ตัวอักษร">
                                </div>
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</p>
                            </div>

                            {{-- Confirm Password --}}
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ยืนยันรหัสผ่าน <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           required
                                           class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                                           placeholder="กรอกรหัสผ่านอีกครั้ง">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hotel Assignment Section --}}
                    <div class="pb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            กำหนดโรงแรม (ไม่บังคับ)
                        </h2>

                        <div>
                            <label for="managed_hotel_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เลือกโรงแรมที่ต้องการมอบหมาย
                            </label>
                            <select name="managed_hotel_id"
                                    id="managed_hotel_id"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                                <option value="">-- ไม่กำหนดโรงแรม --</option>
                                @foreach($availableHotels as $hotel)
                                    <option value="{{ $hotel->id }}" {{ old('managed_hotel_id') == $hotel->id ? 'selected' : '' }}>
                                        {{ $hotel->name }} ({{ $hotel->city }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                คุณสามารถเลือกโรงแรมที่ต้องการให้เจ้าของคนนี้ดูแล หรือข้ามไปก่อนและกำหนดทีหลัง
                            </p>
                            @if($availableHotels->isEmpty())
                                <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                        ⚠️ ขณะนี้ไม่มีโรงแรมที่ยังไม่มีเจ้าของ คุณสามารถกำหนดโรงแรมทีหลังได้
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.hotel-owners.index') }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
