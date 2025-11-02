@extends('layouts.admin')

@section('title', 'สร้างผู้ใช้ใหม่')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
        ← กลับไปรายการผู้ใช้
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">สร้างผู้ใช้ใหม่</h2>

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
                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
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
                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
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
                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-500 @enderror">
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
                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="role"
                        name="role"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('role') border-red-500 @enderror">
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
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded">
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
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.users.index') }}"
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 rounded-md hover:bg-gray-200 dark:hover:bg-slate-600">
                    ยกเลิก
                </a>

                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    สร้างผู้ใช้
                </button>
            </div>
        </form>
    </div>

    <!-- Tips Card -->
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
        <h3 class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">💡 เคล็ดลับ</h3>
        <ul class="text-sm text-yellow-800 dark:text-yellow-300 space-y-1">
            <li>• ใช้อีเมลที่ถูกต้องเพื่อให้ผู้ใช้สามารถรับอีเมลยืนยันได้</li>
            <li>• รหัสผ่านที่แข็งแกร่งควรมีตัวพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ</li>
            <li>• พิจารณาให้ Role ที่เหมาะสมกับหน้าที่ของผู้ใช้</li>
            <li>• Super Admin ควรมีเพียงไม่กี่คนเพื่อความปลอดภัย</li>
        </ul>
    </div>
</div>
@endsection
