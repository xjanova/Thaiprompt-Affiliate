@extends('layouts.admin')

@section('title', 'เพิ่มตำแหน่งใหม่')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">💼 เพิ่มตำแหน่งใหม่</h1>
                <p class="text-green-100">กรอกข้อมูลตำแหน่งงานใหม่</p>
            </div>
            <a href="{{ route('admin.hrm.positions.index') }}"
               class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.hrm.positions.store') }}" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อตำแหน่ง <span class="text-red-500">*</span></label>
                    <input type="text" name="position_name" value="{{ old('position_name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 @error('position_name') border-red-500 @enderror">
                    @error('position_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสตำแหน่ง <span class="text-red-500">*</span></label>
                    <input type="text" name="position_code" value="{{ old('position_code') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 @error('position_code') border-red-500 @enderror">
                    @error('position_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แผนก <span class="text-red-500">*</span></label>
                    <select name="department_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 @error('department_id') border-red-500 @enderror">
                        <option value="">เลือกแผนก</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับ</label>
                    <select name="level"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">เลือกระดับ</option>
                        <option value="entry" {{ old('level') == 'entry' ? 'selected' : '' }}>Entry</option>
                        <option value="junior" {{ old('level') == 'junior' ? 'selected' : '' }}>Junior</option>
                        <option value="mid" {{ old('level') == 'mid' ? 'selected' : '' }}>Mid</option>
                        <option value="senior" {{ old('level') == 'senior' ? 'selected' : '' }}>Senior</option>
                        <option value="lead" {{ old('level') == 'lead' ? 'selected' : '' }}>Lead</option>
                        <option value="manager" {{ old('level') == 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="director" {{ old('level') == 'director' ? 'selected' : '' }}>Director</option>
                        <option value="executive" {{ old('level') == 'executive' ? 'selected' : '' }}>Executive</option>
                    </select>
                    @error('level')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เงินเดือนขั้นต่ำ</label>
                    <input type="number" name="min_salary" value="{{ old('min_salary') }}" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 @error('min_salary') border-red-500 @enderror">
                    @error('min_salary')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เงินเดือนสูงสุด</label>
                    <input type="number" name="max_salary" value="{{ old('max_salary') }}" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 @error('max_salary') border-red-500 @enderror">
                    @error('max_salary')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">{{ old('description') }}</textarea>
                    @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Responsibilities and Requirements -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ความรับผิดชอบและคุณสมบัติ</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความรับผิดชอบ</label>
                    <textarea name="responsibilities" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                              placeholder="ระบุหน้าที่และความรับผิดชอบของตำแหน่งนี้">{{ old('responsibilities') }}</textarea>
                    @error('responsibilities')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คุณสมบัติที่ต้องการ</label>
                    <textarea name="requirements" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                              placeholder="ระบุคุณสมบัติและทักษะที่ต้องการสำหรับตำแหน่งนี้">{{ old('requirements') }}</textarea>
                    @error('requirements')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะ</h3>
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    เปิดใช้งานตำแหน่งนี้
                </label>
            </div>
            @error('is_active')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.hrm.positions.index') }}"
               class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                บันทึก
            </button>
        </div>
    </form>
</div>
@endsection
