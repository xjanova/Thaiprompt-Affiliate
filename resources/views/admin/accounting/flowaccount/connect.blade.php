@extends('layouts.admin')

@section('title', 'เชื่อมต่อ FlowAccount')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center mb-4">
            <a href="{{ route('admin.accounting.flowaccount.index') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 mr-4">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">เชื่อมต่อ FlowAccount</h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400">กรอกข้อมูล API Credentials ของคุณเพื่อเชื่อมต่อกับ FlowAccount</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <form action="{{ route('admin.accounting.flowaccount.connect') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Client ID -->
            <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client ID <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="client_id"
                    id="client_id"
                    required
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="กรอก Client ID ของคุณ"
                    value="{{ old('client_id') }}"
                >
                @error('client_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Client Secret -->
            <div>
                <label for="client_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client Secret <span class="text-red-500">*</span>
                </label>
                <input
                    type="password"
                    name="client_secret"
                    id="client_secret"
                    required
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="กรอก Client Secret ของคุณ"
                    value="{{ old('client_secret') }}"
                >
                @error('client_secret')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="{{ route('admin.accounting.flowaccount.index') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
                <button
                    type="submit"
                    class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all"
                >
                    <i class="fas fa-link mr-2"></i>เชื่อมต่อ FlowAccount
                </button>
            </div>
        </form>
    </div>

    <!-- Instructions Card -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-4">
            <i class="fas fa-info-circle mr-2"></i>วิธีการหา API Credentials
        </h3>
        <ol class="list-decimal list-inside space-y-2 text-blue-800 dark:text-blue-300">
            <li>เข้าสู่ระบบ <a href="https://developers.flowaccount.com" target="_blank" class="underline hover:text-blue-600">FlowAccount Developer Portal</a></li>
            <li>ไปที่เมนู "Applications" หรือ "My Apps"</li>
            <li>สร้าง Application ใหม่ หรือเลือก Application ที่มีอยู่</li>
            <li>คัดลอก <strong>Client ID</strong> และ <strong>Client Secret</strong></li>
            <li>ตั้งค่า <strong>Redirect URI</strong> เป็น: <code class="bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded">{{ config('services.flowaccount.redirect_uri', url('/admin/accounting/flowaccount/callback')) }}</code></li>
            <li>นำ Client ID และ Client Secret มากรอกในฟอร์มด้านบน</li>
        </ol>
    </div>

    <!-- Security Notice -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mt-4">
        <div class="flex items-start">
            <i class="fas fa-shield-alt text-yellow-600 dark:text-yellow-400 mt-1 mr-3"></i>
            <div>
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-300 mb-1">ความปลอดภัย</h4>
                <p class="text-sm text-yellow-800 dark:text-yellow-300">
                    ข้อมูล API Credentials ของคุณจะถูกเก็บอย่างปลอดภัยและเข้ารหัสในระบบ
                    เราจะไม่แชร์ข้อมูลนี้กับบุคคลที่สาม
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
