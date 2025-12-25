@extends('layouts.admin-v3')

@section('title', 'แก้ไขผู้ใช้ - ' . $user->name)

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายละเอียด
        </a>
    </div>

    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 dark:from-orange-700 dark:via-amber-700 dark:to-yellow-700 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-user-edit"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-user-edit text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">แก้ไขผู้ใช้</h1>
                    <p class="text-orange-100 text-lg mt-1">อัพเดทข้อมูลผู้ใช้งาน</p>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
        {{-- User Info Display --}}
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 backdrop-blur-sm rounded-xl p-4 mb-6 flex items-center border-l-4 border-orange-400 dark:border-orange-500">
            {{-- Avatar --}}
            <img src="{{ $user->profile_picture_url }}"
                 alt="{{ $user->name }}"
                 class="h-16 w-16 rounded-full object-cover ring-2 ring-orange-400 dark:ring-orange-500"
                 onerror="this.onerror=null; this.outerHTML='<div class=\'h-16 w-16 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-2xl font-bold text-orange-600 dark:text-orange-300 ring-2 ring-orange-400 dark:ring-orange-500\'>{{ strtoupper(substr($user->name, 0, 1)) }}</div>';">

            <div class="ml-4 flex-1">
                <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ $user->name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                <span class="inline-block mt-1 px-2 py-1 text-xs font-semibold rounded-full bg-orange-400/20 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300">
                    {{ ucfirst($user->role ?? 'user') }}
                </span>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-edit text-orange-600 dark:text-orange-400"></i>
            ข้อมูลผู้ใช้
        </h2>

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
                       value="{{ old('email', $user->email) }}"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('email') border-red-500 @enderror">
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
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white rounded-xl focus:outline-none transition-all duration-200 @error('role') border-red-500 @enderror">
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
                    <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300 bg-white/60 dark:bg-white/10 backdrop-blur-sm border-l-4 border-yellow-400 dark:border-yellow-500 p-3 rounded-xl">
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
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white rounded-xl focus:outline-none transition-all duration-200 @error('menu_theme_preference') border-red-500 @enderror">
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
            <div class="border-t border-gray-200 dark:border-white/10 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เปลี่ยนรหัสผ่าน (ถ้าต้องการ)</h3>

                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รหัสผ่านใหม่
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('password') border-red-500 @enderror">
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
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-white/10">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-white/60 dark:bg-white/10 backdrop-blur-sm border border-gray-200 dark:border-white/20 rounded-xl hover:bg-gray-100 dark:hover:bg-white/20 transition-all duration-200">
                    ยกเลิก
                </a>

                <button type="submit"
                        class="px-6 py-2.5 text-white font-semibold rounded-xl focus:outline-none shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5"
                        style="background: var(--arrow-x-primary-gradient)">
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>

        <!-- Danger Zone -->
        @if(!$user->is_super_admin)
        <div class="mt-8 pt-6 border-t border-red-200 dark:border-red-500/50">
            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Danger Zone</h3>
            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-l-4 border-red-500 dark:border-red-500 rounded-xl p-4 shadow-lg">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-medium text-red-900 dark:text-red-300">ลบผู้ใช้</h4>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                            การลบผู้ใช้จะไม่สามารถกู้คืนได้ โปรดใช้ความระมัดระวัง
                        </p>
                        @if($user->mlmMembers()->exists())
                            <p class="mt-2 text-sm text-red-700 dark:text-red-400 font-medium">
                                ⚠️ ผู้ใช้นี้เป็น MLM Member - การลบจะส่งผลต่อ downline
                            </p>
                        @endif
                    </div>
                    <form action="{{ route('admin.users.destroy', $user) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-5 py-2.5 text-white font-semibold rounded-xl focus:outline-none shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5"
                                style="background-color: var(--arrow-x-error)">
                            ลบผู้ใช้
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-white/10">
            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-l-4 border-blue-400 dark:border-blue-500 rounded-xl p-4 shadow-lg">
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
</div>
@endsection
