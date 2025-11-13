@extends('layouts.admin')

@section('title', 'แก้ไขผู้ใช้ - ' . $user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
        ← กลับไปรายละเอียด
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">แก้ไขผู้ใช้</h2>

        <!-- User Info Display -->
        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 mb-6 flex items-center">
            <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-xl font-bold text-indigo-600 dark:text-indigo-300">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="ml-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อ <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $user->name) }}"
                       required
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
                       value="{{ old('email', $user->email) }}"
                       required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="role"
                        name="role"
                        required
                        @if($user->is_super_admin) disabled @endif
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('role') border-red-500 @enderror">
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>
                        User - ผู้ใช้ทั่วไป
                    </option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>
                        Admin - ผู้ดูแลระบบ
                    </option>
                    <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>
                        Super Admin - ผู้ดูแลระบบสูงสุด
                    </option>
                </select>

                @if($user->is_super_admin)
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-500 p-3 rounded">
                        <strong>หมายเหตุ:</strong> ไม่สามารถเปลี่ยน Role ของ Super Admin ได้
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <strong>คำอธิบาย:</strong><br>
                        • <strong>User:</strong> สามารถใช้งานระบบพื้นฐานได้<br>
                        • <strong>Admin:</strong> สามารถจัดการระบบและดูข้อมูลทั้งหมด<br>
                        • <strong>Super Admin:</strong> สิทธิ์สูงสุด สามารถทำทุกอย่างได้
                    </p>
                @endif

                @error('role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Menu Theme Preference -->
            <div>
                <label for="menu_theme_preference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ธีมเมนู
                </label>
                <select id="menu_theme_preference"
                        name="menu_theme_preference"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('menu_theme_preference') border-red-500 @enderror">
                    <option value="millennium" {{ old('menu_theme_preference', $user->menu_theme_preference ?? 'millennium') === 'millennium' ? 'selected' : '' }}>
                        Millennium Theme (Windows 11 Style)
                    </option>
                    <option value="classic_x" {{ old('menu_theme_preference', $user->menu_theme_preference ?? 'millennium') === 'classic_x' ? 'selected' : '' }}>
                        Classic X Theme (WordPress Style)
                    </option>
                </select>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <strong>คำอธิบาย:</strong><br>
                    • <strong>Millennium:</strong> สไตล์ Windows 11 ที่ทันสมัย มี Taskbar แบบ Floating พร้อม Blur Effect<br>
                    • <strong>Classic X:</strong> สไตล์ WordPress ที่ได้รับการยกระดับ มี Sidebar แบบ Professional
                </p>
                @error('menu_theme_preference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password (Optional) -->
            <div class="border-t dark:border-slate-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เปลี่ยนรหัสผ่าน (ถ้าต้องการ)</h3>

                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รหัสผ่านใหม่
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            ปล่อยว่างไว้ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน
                        </p>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ยืนยันรหัสผ่านใหม่
                        </label>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 rounded-md hover:bg-gray-200 dark:hover:bg-slate-600">
                    ยกเลิก
                </a>

                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>

        <!-- Danger Zone -->
        @if(!$user->is_super_admin)
        <div class="mt-8 pt-6 border-t border-red-200 dark:border-red-800">
            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Danger Zone</h3>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-medium text-red-900 dark:text-red-300">ลบผู้ใช้</h4>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                            การลบผู้ใช้จะไม่สามารถกู้คืนได้ โปรดใช้ความระมัดระวัง
                        </p>
                        @if($user->affiliate)
                            <p class="mt-2 text-sm text-red-700 dark:text-red-400 font-medium">
                                ⚠️ ผู้ใช้นี้เป็น Affiliate - การลบจะส่งผลต่อ downline
                            </p>
                        @endif
                    </div>
                    <form action="{{ route('admin.users.destroy', $user) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            ลบผู้ใช้
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-700">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-blue-400 dark:text-blue-300 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>ข้อมูล:</strong> ไม่สามารถลบบัญชี Super Admin ได้เพื่อความปลอดภัยของระบบ
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
