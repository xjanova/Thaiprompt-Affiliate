@extends('layouts.admin-v3')

@section('title', 'สร้าง Email Template')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">สร้าง Email Template</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">สร้าง Template อีเมลสำหรับใช้งานในระบบ</p>
        </div>
        <a href="{{ route('admin.email.templates.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700">
            ← กลับ
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.email.templates.store') }}" method="POST" class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    ชื่อ Template <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}"
                       placeholder="welcome_email"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใช้ snake_case เช่น welcome_email, password_reset</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    หมวดหมู่ <span class="text-red-500">*</span>
                </label>
                <select name="category" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                    <option value="system">System - ระบบ</option>
                    <option value="transactional">Transactional - ธุรกรรม</option>
                    <option value="marketing">Marketing - การตลาด</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    ภาษา <span class="text-red-500">*</span>
                </label>
                <select name="language" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                    <option value="th">ไทย (TH)</option>
                    <option value="en">English (EN)</option>
                </select>
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
            </div>
        </div>

        <!-- Subject -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                หัวข้ออีเมล (Subject) <span class="text-red-500">*</span>
            </label>
            <input type="text" name="subject" value="{{ old('subject') }}"
                   placeholder="ยินดีต้อนรับสู่ {{app_name}}"
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('subject') border-red-500 @enderror"
                   required>
            @error('subject')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Body HTML -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                เนื้อหา HTML <span class="text-red-500">*</span>
            </label>
            <textarea name="body_html" rows="10"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm @error('body_html') border-red-500 @enderror"
                      required>{{ old('body_html') }}</textarea>
            @error('body_html')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">สามารถใช้ HTML Tags และ Variables ได้</p>
        </div>

        <!-- Body Text -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                เนื้อหา Plain Text
            </label>
            <textarea name="body_text" rows="6"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm"
                      placeholder="ใช้สำหรับ email client ที่ไม่รองรับ HTML">{{ old('body_text') }}</textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional - สำหรับ email client ที่ไม่รองรับ HTML</p>
        </div>

        <!-- Variables -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                Variables (คั่นด้วยจุลภาค)
            </label>
            <input type="text" name="variables" value="{{ old('variables') }}"
                   placeholder="user_name, app_name, activation_link"
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตัวแปรที่ใช้ใน Template เช่น user_name, app_name</p>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                คำอธิบาย
            </label>
            <textarea name="description" rows="3"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description') }}</textarea>
        </div>

        <!-- Variables Help -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">💡 Variables ที่ใช้ได้</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm text-blue-800 dark:text-blue-200">
                <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">@{{user_name}}</code>
                <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">@{{user_email}}</code>
                <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">@{{app_name}}</code>
                <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">@{{app_url}}</code>
                <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">@{{amount}}</code>
                <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">@{{action_url}}</code>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
            <a href="{{ route('admin.email.templates.index') }}" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600">
                ยกเลิก
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                บันทึก Template
            </button>
        </div>
    </form>
</div>
@endsection
