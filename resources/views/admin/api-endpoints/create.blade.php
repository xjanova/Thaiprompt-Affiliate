@extends('layouts.admin-v3')

@section('title', 'เพิ่ม API Endpoint ใหม่')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    🔌 เพิ่ม API Endpoint ใหม่
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    กรอกรายละเอียดเพื่อเพิ่ม API endpoint ใหม่
                </p>
            </div>
            <a href="{{ route('admin.api-management.endpoints.index') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.api-management.endpoints.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information Section -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">📋 ข้อมูลพื้นฐาน</h3>

                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อ Endpoint <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                           placeholder="เช่น Get User Profile">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Path -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Path <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="path" value="{{ old('path') }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono @error('path') border-red-500 @enderror"
                           placeholder="/api/v1/users/{id}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใช้ {parameter} สำหรับ dynamic parameters</p>
                    @error('path')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Method and Category -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            HTTP Method <span class="text-red-500">*</span>
                        </label>
                        <select name="method" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('method') border-red-500 @enderror">
                            <option value="">เลือก Method</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" {{ old('method') == $method ? 'selected' : '' }}>
                                    {{ $method }}
                                </option>
                            @endforeach
                        </select>
                        @error('method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            หมวดหมู่
                        </label>
                        <input type="text" name="category" value="{{ old('category') }}"
                               list="categories"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('category') border-red-500 @enderror"
                               placeholder="เช่น Users, Products">
                        <datalist id="categories">
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror"
                              placeholder="คำอธิบายเกี่ยวกับ endpoint นี้">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับความสำคัญ
                    </label>
                    <input type="number" name="priority" value="{{ old('priority', 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('priority') border-red-500 @enderror"
                           placeholder="0">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตัวเลขน้อยแสดงก่อน (0 = default)</p>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Documentation Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">📚 เอกสารประกอบ</h3>

                <!-- Parameters -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Parameters (JSON Format)
                    </label>
                    <textarea name="parameters" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono text-sm @error('parameters') border-red-500 @enderror"
                              placeholder='{"id": "required|integer", "name": "string"}'>{!! old('parameters') !!}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">กำหนด parameters ในรูปแบบ JSON</p>
                    @error('parameters')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Response Example -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ตัวอย่าง Response (JSON Format)
                    </label>
                    <textarea name="response_example" rows="6"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono text-sm @error('response_example') border-red-500 @enderror"
                              placeholder='{"status": "success", "data": {...}}'>{!! old('response_example') !!}</textarea>
                    @error('response_example')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Security & Settings Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">🔐 การตั้งค่า</h3>

                <!-- Checkboxes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="requires_auth" value="1" {{ old('requires_auth') ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ต้องการ Authentication</span>
                        </label>
                    </div>
                </div>

                <!-- Allowed Roles -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Roles ที่อนุญาต (คั่นด้วยเครื่องหมายจุลภาค)
                    </label>
                    <input type="text" name="allowed_roles" value="{{ old('allowed_roles') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('allowed_roles') border-red-500 @enderror"
                           placeholder="admin, user, partner">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างเพื่ออนุญาตทุก roles</p>
                    @error('allowed_roles')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Rate Limiting Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">⏱️ การจำกัดอัตรา (Rate Limiting)</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Rate Limit Per Minute -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำกัดต่อนาที
                        </label>
                        <input type="number" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_minute') border-red-500 @enderror"
                               placeholder="60">
                        @error('rate_limit_per_minute')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rate Limit Per Hour -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำกัดต่อชั่วโมง
                        </label>
                        <input type="number" name="rate_limit_per_hour" value="{{ old('rate_limit_per_hour') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_hour') border-red-500 @enderror"
                               placeholder="1000">
                        @error('rate_limit_per_hour')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rate Limit Per Day -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำกัดต่อวัน
                        </label>
                        <input type="number" name="rate_limit_per_day" value="{{ old('rate_limit_per_day') }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('rate_limit_per_day') border-red-500 @enderror"
                               placeholder="10000">
                        @error('rate_limit_per_day')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.api-management.endpoints.index') }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
                    🔌 เพิ่ม Endpoint
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
