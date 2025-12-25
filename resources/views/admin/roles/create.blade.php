@extends('layouts.admin-v3')

@section('title', 'สร้างบทบาทใหม่')

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการบทบาท
        </a>
    </div>

    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-plus-circle text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">สร้างบทบาทใหม่</h1>
                    <p class="text-indigo-100 text-lg mt-1">กำหนดชื่อและสิทธิ์สำหรับบทบาทใหม่</p>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-4xl mx-auto">

    <!-- Form -->
    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 space-y-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <!-- Role Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    ชื่อบทบาท (ภาษาอังกฤษ) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
                       placeholder="เช่น manager, supervisor"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">ใช้ตัวพิมพ์เล็ก ไม่มีช่องว่าง สำหรับระบบภายใน</p>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Display Name -->
            <div>
                <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    ชื่อที่แสดง <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="display_name"
                       name="display_name"
                       value="{{ old('display_name') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
                       placeholder="เช่น ผู้จัดการ, ผู้ดูแลระบบ"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">ชื่อที่จะแสดงให้ผู้ใช้เห็น</p>
                @error('display_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    คำอธิบาย
                </label>
                <textarea id="description"
                          name="description"
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
                          placeholder="อธิบายหน้าที่และความรับผิดชอบของบทบาทนี้">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Permissions -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-4">
                    สิทธิ์การเข้าถึง
                </label>

                @if($permissions->count() > 0)
                    <div class="space-y-4">
                        @foreach($permissions as $category => $categoryPermissions)
                            <div class="border border-gray-200 dark:border-gray-700 dark:border-gray-700 rounded-xl p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    {{ \App\Models\Permission::getCategoryDisplayName($category) }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($categoryPermissions as $permission)
                                        <label class="flex items-start cursor-pointer hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 p-2 rounded">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->id }}"
                                                   {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                                   class="mt-1 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $permission->display_name }}
                                                </div>
                                                @if($permission->description)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                                        {{ $permission->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm">ยังไม่มีสิทธิ์ในระบบ</p>
                @endif

                @error('permissions')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <a href="{{ route('admin.roles.index') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึก
                </button>
            </div>
        </div>
    </form>

    @if(session('error'))
    <div class="mt-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl">
        {{ session('error') }}
    </div>
    @endif
</div>
</div>
@endsection
