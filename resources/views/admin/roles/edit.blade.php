@extends('layouts.admin-v3')

@section('title', 'แก้ไขบทบาท')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.roles.index') }}" class="text-gray-600 dark:text-gray-400 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← กลับ
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-gray-200">
                    🔐 แก้ไขบทบาท
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">
                    แก้ไขข้อมูลและสิทธิ์ของบทบาท: {{ $role->display_name }}
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 space-y-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <!-- Role Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    ชื่อบทบาท (ภาษาอังกฤษ) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $role->name) }}"
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
                       value="{{ old('display_name', $role->display_name) }}"
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
                          placeholder="อธิบายหน้าที่และความรับผิดชอบของบทบาทนี้">{{ old('description', $role->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- System Role Notice -->
            @if($role->is_system_role)
            <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            บทบาทระบบ
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>นี่คือบทบาทระบบที่สำคัญต่อการทำงานของแอปพลิเคชัน กรุณาระมัดระวังในการแก้ไข</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                                                   {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}
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
                    บันทึกการแก้ไข
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
@endsection
