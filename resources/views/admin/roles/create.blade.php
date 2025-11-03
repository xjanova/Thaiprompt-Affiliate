@extends('layouts.admin')

@section('title', 'สร้างบทบาทใหม่')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.roles.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← กลับ
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    🔐 สร้างบทบาทใหม่
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    กำหนดชื่อและสิทธิ์สำหรับบทบาทใหม่
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 space-y-6">
            <!-- Role Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อบทบาท (ภาษาอังกฤษ) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
                       placeholder="เช่น manager, supervisor"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้ตัวพิมพ์เล็ก ไม่มีช่องว่าง สำหรับระบบภายใน</p>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Display Name -->
            <div>
                <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อที่แสดง <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="display_name"
                       name="display_name"
                       value="{{ old('display_name') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
                       placeholder="เช่น ผู้จัดการ, ผู้ดูแลระบบ"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ชื่อที่จะแสดงให้ผู้ใช้เห็น</p>
                @error('display_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    คำอธิบาย
                </label>
                <textarea id="description"
                          name="description"
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
                          placeholder="อธิบายหน้าที่และความรับผิดชอบของบทบาทนี้">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Permissions -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    สิทธิ์การเข้าถึง
                </label>

                @if($permissions->count() > 0)
                    <div class="space-y-4">
                        @foreach($permissions as $category => $categoryPermissions)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    {{ \App\Models\Permission::getCategoryDisplayName($category) }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($categoryPermissions as $permission)
                                        <label class="flex items-start cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->id }}"
                                                   {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                                   class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $permission->display_name }}
                                                </div>
                                                @if($permission->description)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
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
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มีสิทธิ์ในระบบ</p>
                @endif

                @error('permissions')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.roles.index') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึก
                </button>
            </div>
        </div>
    </form>

    @if(session('error'))
    <div class="mt-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif
</div>
@endsection
