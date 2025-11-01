@extends('layouts.admin')

@section('title', 'เพิ่ม Email Provider')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="providerForm()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">เพิ่ม Email Provider</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">เพิ่มผู้ให้บริการส่งอีเมลใหม่</p>
        </div>
        <a href="{{ route('admin.email.providers') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            ← กลับ
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.email.providers.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อ Provider <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}"
                       placeholder="gmail_smtp_primary"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">ชื่อเฉพาะ (unique) สำหรับระบุ provider</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อแสดง <span class="text-red-500">*</span>
                </label>
                <input type="text" name="display_name" value="{{ old('display_name') }}"
                       placeholder="Gmail SMTP (Primary)"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('display_name') border-red-500 @enderror"
                       required>
                @error('display_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Type Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ประเภท Provider <span class="text-red-500">*</span>
            </label>
            <select name="type" x-model="providerType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                <option value="">-- เลือกประเภท --</option>
                <option value="smtp">SMTP (รวม Gmail SMTP, AWS SES, Mailgun, etc.)</option>
                <option value="api">API (Gmail API, SendGrid API, etc.)</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">
                💡 <strong>SMTP:</strong> ใช้สำหรับส่งผ่าน SMTP server (Gmail SMTP, Generic SMTP)<br>
                💡 <strong>API:</strong> ใช้สำหรับส่งผ่าน API (Gmail API)
            </p>
        </div>

        <!-- Priority & Limits -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลำดับความสำคัญ
                </label>
                <input type="number" name="priority" value="{{ old('priority', 50) }}" min="0" max="100"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">0-100 (เลขสูง = ใช้ก่อน)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำกัดรายวัน
                </label>
                <input type="number" name="daily_limit" value="{{ old('daily_limit', 500) }}" min="1"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำกัดรายชั่วโมง
                </label>
                <input type="number" name="hourly_limit" value="{{ old('hourly_limit', 50) }}" min="1"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>

        <!-- Configuration: SMTP -->
        <div x-show="providerType === 'smtp'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2">
                🔧 การตั้งค่า SMTP
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        SMTP Host <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="configuration[host]" value="{{ old('configuration.host') }}"
                           placeholder="smtp.gmail.com หรือ mail.example.com"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">Gmail: smtp.gmail.com | Office365: smtp.office365.com</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Port <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="configuration[port]" value="{{ old('configuration.port', 587) }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">587 (TLS) หรือ 465 (SSL)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="configuration[username]" value="{{ old('configuration.username') }}"
                           placeholder="your-email@gmail.com"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Password / App Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="configuration[password]" value="{{ old('configuration.password') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-yellow-600">
                        ⚠️ สำหรับ Gmail ต้องใช้ App Password (ไม่ใช่รหัสผ่านปกติ)
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Encryption
                    </label>
                    <select name="configuration[encryption]" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="tls">TLS (แนะนำ)</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Email
                    </label>
                    <input type="email" name="configuration[from_email]" value="{{ old('configuration.from_email') }}"
                           placeholder="noreply@example.com"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    From Name
                </label>
                <input type="text" name="configuration[from_name]" value="{{ old('configuration.from_name') }}"
                       placeholder="ThaiPrompt Affiliate"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>

        <!-- Configuration: Gmail API -->
        <div x-show="providerType === 'api'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2">
                🔧 การตั้งค่า Gmail API
            </h3>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    💡 <strong>ขั้นตอนการตั้งค่า Gmail API:</strong><br>
                    1. ไปที่ <a href="https://console.cloud.google.com" target="_blank" class="underline">Google Cloud Console</a><br>
                    2. สร้าง Project และ Enable Gmail API<br>
                    3. สร้าง OAuth 2.0 Client ID<br>
                    4. ดูคู่มือละเอียด: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">docs/EMAIL_SETUP_GUIDE.md</code>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client ID <span class="text-red-500">*</span>
                </label>
                <input type="text" name="configuration[client_id]" value="{{ old('configuration.client_id') }}"
                       placeholder="xxxxx.apps.googleusercontent.com"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client Secret <span class="text-red-500">*</span>
                </label>
                <input type="password" name="configuration[client_secret]" value="{{ old('configuration.client_secret') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Redirect URI <span class="text-red-500">*</span>
                </label>
                <input type="text" name="configuration[redirect_uri]" value="{{ old('configuration.redirect_uri', url('/admin/email/gmail/callback')) }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">URL callback หลัง OAuth (ต้องตรงกับที่ตั้งค่าใน Google Cloud Console)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Email
                    </label>
                    <input type="email" name="configuration[from_email]" value="{{ old('configuration.from_email') }}"
                           placeholder="your-email@gmail.com"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Name
                    </label>
                    <input type="text" name="configuration[from_name]" value="{{ old('configuration.from_name') }}"
                           placeholder="ThaiPrompt Affiliate"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="flex items-center space-x-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
            </label>

            <label class="flex items-center">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ตั้งเป็น Default Provider</span>
            </label>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('admin.email.providers') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                ยกเลิก
            </a>
            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:from-blue-700 hover:to-cyan-700">
                💾 บันทึก Provider
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function providerForm() {
    return {
        providerType: '{{ old("type") }}',
    }
}
</script>
@endpush
@endsection
