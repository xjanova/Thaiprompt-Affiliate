@extends('layouts.admin-v3')

@section('title', 'แก้ไข Email Provider')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="providerForm()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไข Email Provider</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">แก้ไขการตั้งค่าผู้ให้บริการส่งอีเมล</p>
        </div>
        <a href="{{ route('admin.email.providers') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            ← กลับ
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.email.providers.update', $provider) }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อ Provider
                </label>
                <input type="text" value="{{ $provider->name }}" disabled
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">
                <p class="mt-1 text-xs text-gray-500">ชื่อไม่สามารถเปลี่ยนแปลงได้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อแสดง <span class="text-red-500">*</span>
                </label>
                <input type="text" name="display_name" value="{{ old('display_name', $provider->display_name) }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('display_name') border-red-500 @enderror"
                       required>
                @error('display_name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Type (read-only) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ประเภท Provider
            </label>
            <input type="text" value="{{ ucfirst($provider->type) }}" disabled
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">
            <p class="mt-1 text-xs text-gray-500">ประเภทไม่สามารถเปลี่ยนแปลงได้</p>
        </div>

        <!-- Priority & Limits -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลำดับความสำคัญ
                </label>
                <input type="number" name="priority" value="{{ old('priority', $provider->priority) }}" min="0" max="100"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">0-100 (เลขสูง = ใช้ก่อน)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำกัดรายวัน
                </label>
                <input type="number" name="daily_limit" value="{{ old('daily_limit', $provider->daily_limit) }}" min="1"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำกัดรายชั่วโมง
                </label>
                <input type="number" name="hourly_limit" value="{{ old('hourly_limit', $provider->hourly_limit) }}" min="1"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>

        <!-- Configuration: SMTP -->
        @if($provider->type === 'smtp')
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2">
                🔧 การตั้งค่า SMTP
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        SMTP Host <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="configuration[host]" value="{{ old('configuration.host', $provider->configuration['host'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Port <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="configuration[port]" value="{{ old('configuration.port', $provider->configuration['port'] ?? 587) }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="configuration[username]" value="{{ old('configuration.username', $provider->configuration['username'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Password / App Password
                    </label>
                    <input type="password" name="configuration[password]" value="{{ old('configuration.password', $provider->configuration['password'] ?? '') }}"
                           placeholder="ไม่เปลี่ยนแปลงหากไม่กรอก"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">เว้นว่างไว้หากไม่ต้องการเปลี่ยน</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Encryption
                    </label>
                    <select name="configuration[encryption]" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="tls" {{ ($provider->configuration['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($provider->configuration['encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Email
                    </label>
                    <input type="email" name="configuration[from_email]" value="{{ old('configuration.from_email', $provider->configuration['from_email'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    From Name
                </label>
                <input type="text" name="configuration[from_name]" value="{{ old('configuration.from_name', $provider->configuration['from_name'] ?? '') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        @endif

        <!-- Configuration: Gmail API -->
        @if($provider->type === 'api')
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2">
                🔧 การตั้งค่า Gmail API
            </h3>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    💡 <strong>ขั้นตอนการตั้งค่า Gmail API:</strong><br>
                    ดูคู่มือละเอียด: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">docs/EMAIL_SETUP_GUIDE.md</code>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client ID <span class="text-red-500">*</span>
                </label>
                <input type="text" name="configuration[client_id]" value="{{ old('configuration.client_id', $provider->configuration['client_id'] ?? '') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client Secret
                </label>
                <input type="password" name="configuration[client_secret]" value="{{ old('configuration.client_secret', $provider->configuration['client_secret'] ?? '') }}"
                       placeholder="ไม่เปลี่ยนแปลงหากไม่กรอก"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">เว้นว่างไว้หากไม่ต้องการเปลี่ยน</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Redirect URI <span class="text-red-500">*</span>
                </label>
                <input type="text" name="configuration[redirect_uri]" value="{{ old('configuration.redirect_uri', $provider->configuration['redirect_uri'] ?? '') }}"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Email
                    </label>
                    <input type="email" name="configuration[from_email]" value="{{ old('configuration.from_email', $provider->configuration['from_email'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Name
                    </label>
                    <input type="text" name="configuration[from_name]" value="{{ old('configuration.from_name', $provider->configuration['from_name'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>
        @endif

        <!-- Usage Statistics -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">📊 สถิติการใช้งาน</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">ส่งวันนี้:</span>
                    <strong class="ml-1 text-gray-900 dark:text-white">{{ $provider->sent_today }}</strong>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">ส่งชั่วโมงนี้:</span>
                    <strong class="ml-1 text-gray-900 dark:text-white">{{ $provider->sent_this_hour }}</strong>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">สำเร็จ:</span>
                    <strong class="ml-1 text-green-600 dark:text-green-400">{{ $provider->success_count }}</strong>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">ล้มเหลว:</span>
                    <strong class="ml-1 text-red-600 dark:text-red-400">{{ $provider->failed_count }}</strong>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="flex items-center space-x-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $provider->is_active) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
            </label>

            <label class="flex items-center">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default', $provider->is_default) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ตั้งเป็น Default Provider</span>
            </label>
        </div>

        <!-- Buttons -->
        <div class="flex justify-between pt-4 border-t">
            <!-- Delete Button -->
            @if(!$provider->is_default)
            <form action="{{ route('admin.email.providers.destroy', $provider) }}" method="POST"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Provider นี้?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    🗑️ ลบ Provider
                </button>
            </form>
            @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                ⚠️ ไม่สามารถลบ Default Provider ได้
            </div>
            @endif

            <!-- Save Buttons -->
            <div class="flex space-x-3">
                <a href="{{ route('admin.email.providers') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:from-blue-700 hover:to-cyan-700">
                    💾 บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function providerForm() {
    return {
        // Provider form logic can be added here if needed
    }
}
</script>
@endpush
@endsection
