@extends('layouts.admin-v3')

@section('title', 'สร้างผู้ใช้ใหม่')

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการผู้ใช้
        </a>
    </div>

    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-500 via-emerald-500 to-teal-600 dark:from-green-700 dark:via-emerald-700 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-user-plus text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">สร้างผู้ใช้ใหม่</h1>
                    <p class="text-green-100 text-lg mt-1">เพิ่มผู้ใช้งานเข้าสู่ระบบ</p>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-edit text-green-600 dark:text-green-400"></i>
            ข้อมูลผู้ใช้
        </h2>

        <!-- Create Form -->
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อ <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       placeholder="ชื่อ-นามสกุล"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    อีเมล <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       placeholder="example@email.com"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    รหัสผ่าน <span class="text-red-500">*</span>
                </label>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       placeholder="อย่างน้อย 8 ตัวอักษร"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('password') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร
                </p>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ยืนยันรหัสผ่าน <span class="text-red-500">*</span>
                </label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       required
                       placeholder="กรอกรหัสผ่านอีกครั้ง"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="role"
                        name="role"
                        required
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white rounded-xl focus:outline-none transition-all duration-200 @error('role') border-red-500 @enderror">
                    <option value="">-- เลือก Role --</option>
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>
                        User - ผู้ใช้ทั่วไป
                    </option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                        Admin - ผู้ดูแลระบบ
                    </option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>
                        Super Admin - ผู้ดูแลระบบสูงสุด
                    </option>
                </select>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <strong>คำอธิบาย:</strong><br>
                    • <strong>User:</strong> สามารถใช้งานระบบพื้นฐานได้<br>
                    • <strong>Admin:</strong> สามารถจัดการระบบและดูข้อมูลทั้งหมด<br>
                    • <strong>Super Admin:</strong> สิทธิ์สูงสุด สามารถทำทุกอย่างได้
                </p>

                @error('role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Information Box -->
            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded-xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>หมายเหตุ:</strong> ผู้ใช้ที่สร้างขึ้นจะต้องยืนยันอีเมลก่อนใช้งานระบบบางส่วน
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-white/10">
                <a href="{{ route('admin.users.index') }}"
                   class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-white/60 dark:bg-white/10 backdrop-blur-sm border border-gray-200 dark:border-white/20 rounded-xl hover:bg-gray-100 dark:hover:bg-white/20 transition-all duration-200">
                    ยกเลิก
                </a>

                <button type="submit"
                        style="background: var(--arrow-x-primary-gradient)"
                        class="px-6 py-2.5 text-white font-semibold rounded-xl focus:outline-none shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                    สร้างผู้ใช้
                </button>
            </div>
        </form>
    </div>

    {{-- Tips Card --}}
    <div class="mt-6 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 backdrop-blur-sm border-l-4 border-yellow-400 dark:border-yellow-500 rounded-xl p-6 shadow-lg">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-yellow-400/20 dark:bg-yellow-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-lightbulb text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-900 dark:text-yellow-300 mb-3 text-lg">💡 เคล็ดลับการสร้างผู้ใช้</h3>
                <ul class="text-sm text-yellow-800 dark:text-yellow-200 space-y-2">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                        <span>ใช้อีเมลที่ถูกต้องเพื่อให้ผู้ใช้สามารถรับอีเมลยืนยันได้</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                        <span>รหัสผ่านที่แข็งแกร่งควรมีตัวพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                        <span>พิจารณาให้ Role ที่เหมาะสมกับหน้าที่ของผู้ใช้</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                        <span>Super Admin ควรมีเพียงไม่กี่คนเพื่อความปลอดภัย</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
