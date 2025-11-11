@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="settingsManager()">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">ตั้งค่าระบบอัพเดท</h1>
        <p class="text-gray-600">จัดการการตั้งค่าสำหรับระบบอัพเดทอัตโนมัติและ GitHub Integration</p>
    </div>

    <!-- Success/Error Messages -->
    <div x-show="message" class="mb-6 p-4 rounded-lg" :class="messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
        <div class="flex items-start">
            <span x-show="messageType === 'success'" class="text-2xl mr-3">✅</span>
            <span x-show="messageType === 'error'" class="text-2xl mr-3">❌</span>
            <div class="flex-1">
                <p class="font-semibold" x-text="message"></p>
            </div>
            <button @click="message = ''" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>
    </div>

    <!-- GitHub Token Settings -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <span class="text-2xl mr-2">🔐</span>
                GitHub Personal Access Token
            </h2>
            <p class="text-sm text-gray-600 mt-1">จำเป็นสำหรับการเข้าถึง private repositories และหลีกเลี่ยง rate limits</p>
        </div>

        <div class="p-6">
            <div class="mb-6">
                <!-- Token Status -->
                <div class="mb-4 p-4 rounded-lg" :class="hasToken ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
                    <div class="flex items-center">
                        <span x-show="hasToken" class="text-green-700 font-medium">✅ Token ถูกตั้งค่าแล้ว</span>
                        <span x-show="!hasToken" class="text-yellow-700 font-medium">⚠️ ยังไม่ได้ตั้งค่า Token</span>
                        <span x-show="hasToken" class="ml-auto text-sm text-gray-600" x-text="maskedToken"></span>
                    </div>
                </div>

                <!-- Token Input -->
                <div class="mb-4">
                    <label for="github_token" class="block text-sm font-medium text-gray-700 mb-2">
                        GitHub Token
                        <a href="https://github.com/settings/tokens" target="_blank" class="ml-2 text-xs text-blue-600 hover:text-blue-700">
                            🔗 สร้าง Token ใหม่
                        </a>
                    </label>
                    <div class="relative">
                        <input
                            :type="showToken ? 'text' : 'password'"
                            id="github_token"
                            x-model="githubToken"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent pr-24"
                            placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        />
                        <button
                            type="button"
                            @click="showToken = !showToken"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
                        >
                            <span x-show="!showToken">👁️ แสดง</span>
                            <span x-show="showToken">🙈 ซ่อน</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Token ต้องขึ้นต้นด้วย <code class="bg-gray-100 px-1 rounded">ghp_</code>, <code class="bg-gray-100 px-1 rounded">gho_</code>, <code class="bg-gray-100 px-1 rounded">ghs_</code>, หรือ <code class="bg-gray-100 px-1 rounded">ghu_</code>
                    </p>
                </div>

                <!-- Token Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-blue-900 mb-2">📚 วิธีสร้าง GitHub Token:</h4>
                    <ol class="text-sm text-blue-800 list-decimal list-inside space-y-1">
                        <li>ไปที่ <a href="https://github.com/settings/tokens" target="_blank" class="underline">GitHub Settings → Tokens</a></li>
                        <li>คลิก "Generate new token (classic)"</li>
                        <li>ตั้งชื่อ: <code class="bg-blue-100 px-1 rounded">Thaiprompt-Affiliate Update System</code></li>
                        <li>เลือก Scope: <code class="bg-blue-100 px-1 rounded">repo</code> (สำหรับ private repository)</li>
                        <li>คลิก "Generate token" และคัดลอก Token</li>
                    </ol>
                    <p class="text-xs text-blue-700 mt-2">💡 Token จะถูก encrypt ก่อนบันทึกลงฐานข้อมูล (ปลอดภัย)</p>
                </div>

                <!-- Test Connection Button -->
                <div class="flex gap-3 mb-4">
                    <button
                        @click="testConnection"
                        :disabled="testing"
                        class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 font-semibold"
                    >
                        <span x-show="!testing">🔌 ทดสอบการเชื่อมต่อ</span>
                        <span x-show="testing">
                            <svg class="inline animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            กำลังทดสอบ...
                        </span>
                    </button>

                    <button
                        @click="clearToken"
                        x-show="hasToken"
                        class="px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold"
                    >
                        🗑️ ลบ Token
                    </button>
                </div>

                <!-- Connection Test Results -->
                <div x-show="testResults" class="p-4 rounded-lg" :class="testSuccess ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                    <h4 class="font-semibold mb-2" :class="testSuccess ? 'text-green-900' : 'text-red-900'">
                        <span x-show="testSuccess">✅ ผลการทดสอบ: ผ่าน</span>
                        <span x-show="!testSuccess">❌ ผลการทดสอบ: ไม่ผ่าน</span>
                    </h4>
                    <div class="text-sm space-y-1" x-html="testResults"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Settings -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <span class="text-2xl mr-2">⚙️</span>
                การตั้งค่าอัพเดท
            </h2>
        </div>

        <div class="p-6 space-y-6">
            <!-- Auto Check -->
            <div class="flex items-start">
                <div class="flex items-center h-6">
                    <input
                        type="checkbox"
                        id="auto_check"
                        x-model="autoCheck"
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                </div>
                <div class="ml-3">
                    <label for="auto_check" class="font-medium text-gray-700">เช็คอัพเดทอัตโนมัติ</label>
                    <p class="text-sm text-gray-500">ตรวจสอบเวอร์ชันใหม่จาก GitHub อัตโนมัติทุกวัน</p>
                </div>
            </div>

            <!-- Auto Update -->
            <div class="flex items-start">
                <div class="flex items-center h-6">
                    <input
                        type="checkbox"
                        id="auto_update"
                        x-model="autoUpdate"
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                </div>
                <div class="ml-3">
                    <label for="auto_update" class="font-medium text-gray-700">อัพเดทอัตโนมัติ</label>
                    <p class="text-sm text-gray-500">ติดตั้งอัพเดทใหม่อัตโนมัติ (ระวัง: อาจทำให้เกิดปัญหาหากมี breaking changes)</p>
                </div>
            </div>

            <!-- Backup Before Update -->
            <div class="flex items-start">
                <div class="flex items-center h-6">
                    <input
                        type="checkbox"
                        id="backup_before_update"
                        x-model="backupBeforeUpdate"
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                </div>
                <div class="ml-3">
                    <label for="backup_before_update" class="font-medium text-gray-700">สำรองข้อมูลก่อนอัพเดท</label>
                    <p class="text-sm text-gray-500">สร้าง backup อัตโนมัติก่อนทำการอัพเดท (แนะนำ)</p>
                </div>
            </div>

            <!-- Notification Email -->
            <div>
                <label for="notification_email" class="block font-medium text-gray-700 mb-2">
                    อีเมลรับการแจ้งเตือน
                </label>
                <input
                    type="email"
                    id="notification_email"
                    x-model="notificationEmail"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="admin@example.com"
                />
                <p class="text-xs text-gray-500 mt-1">อีเมลที่จะได้รับแจ้งเตือนเมื่อมีอัพเดทใหม่</p>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.updates.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
            ยกเลิก
        </a>
        <button
            @click="saveSettings"
            :disabled="saving"
            class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 font-semibold"
        >
            <span x-show="!saving">💾 บันทึกการตั้งค่า</span>
            <span x-show="saving">
                <svg class="inline animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                กำลังบันทึก...
            </span>
        </button>
    </div>

    <!-- Documentation Links -->
    <div class="mt-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
        <h3 class="font-semibold text-gray-800 mb-3">📖 เอกสารประกอบ</h3>
        <div class="grid md:grid-cols-2 gap-3 text-sm">
            <a href="https://github.com/xjanova/Thaiprompt-Affiliate#readme" target="_blank" class="flex items-center text-blue-600 hover:text-blue-700">
                📄 README.md - คู่มือการใช้งาน
            </a>
            <a href="https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/GITHUB_TOKEN_SETUP.md" target="_blank" class="flex items-center text-blue-600 hover:text-blue-700">
                🔐 GITHUB_TOKEN_SETUP.md - วิธีตั้งค่า Token
            </a>
            <a href="https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/CREATE_RELEASE.md" target="_blank" class="flex items-center text-blue-600 hover:text-blue-700">
                📦 CREATE_RELEASE.md - วิธีสร้าง Release
            </a>
            <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens" target="_blank" class="flex items-center text-blue-600 hover:text-blue-700">
                🔗 GitHub PAT Documentation
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function settingsManager() {
    return {
        // Settings data
        githubToken: '',
        autoCheck: false,
        autoUpdate: false,
        backupBeforeUpdate: true,
        notificationEmail: '',

        // UI state
        showToken: false,
        hasToken: false,
        maskedToken: '',
        saving: false,
        testing: false,
        message: '',
        messageType: 'success',
        testResults: '',
        testSuccess: false,

        async init() {
            await this.loadSettings();
        },

        async loadSettings() {
            try {
                const response = await fetch('{{ route('admin.updates.settings') }}', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.autoCheck = data.settings.auto_check || false;
                    this.autoUpdate = data.settings.auto_update || false;
                    this.backupBeforeUpdate = data.settings.backup_before_update !== false;
                    this.notificationEmail = data.settings.notification_email || '';
                    this.hasToken = data.settings.has_github_token || false;
                    this.maskedToken = data.settings.github_token || '';
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการโหลดการตั้งค่า', 'error');
            }
        },

        async saveSettings() {
            this.saving = true;
            this.message = '';

            try {
                const payload = {
                    auto_check: this.autoCheck,
                    auto_update: this.autoUpdate,
                    backup_before_update: this.backupBeforeUpdate,
                    notification_email: this.notificationEmail,
                };

                // Only send token if it's been changed
                if (this.githubToken && this.githubToken.trim() !== '') {
                    payload.github_token = this.githubToken.trim();
                }

                const response = await fetch('{{ route('admin.updates.settings.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.showMessage('✅ ' + data.message, 'success');

                    // Reload settings to get updated masked token
                    await this.loadSettings();

                    // Clear token input after successful save
                    this.githubToken = '';
                    this.showToken = false;
                } else {
                    this.showMessage('❌ ' + data.message, 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการบันทึกการตั้งค่า', 'error');
            }

            this.saving = false;
        },

        async testConnection() {
            this.testing = true;
            this.testResults = '';

            try {
                const response = await fetch('{{ route('admin.updates.test-connection') }}');
                const data = await response.json();

                this.testSuccess = data.success;

                if (data.results && data.results.checks) {
                    let html = '<ul class="space-y-1">';
                    data.results.checks.forEach(check => {
                        const icon = check.status === 'passed' ? '✅' : '❌';
                        const color = check.status === 'passed' ? 'text-green-700' : 'text-red-700';
                        html += `<li class="${color}">${icon} <strong>${check.check}:</strong> ${check.message || check.version || 'OK'}</li>`;
                    });
                    html += '</ul>';
                    this.testResults = html;
                } else {
                    this.testResults = data.message || 'ไม่สามารถทดสอบการเชื่อมต่อได้';
                }
            } catch (error) {
                this.testSuccess = false;
                this.testResults = 'เกิดข้อผิดพลาดในการทดสอบการเชื่อมต่อ';
            }

            this.testing = false;
        },

        async clearToken() {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบ GitHub Token?\n\nระบบจะกลับไปใช้ค่าจาก .env (ถ้ามี)')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.updates.settings.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        github_token: ''
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showMessage('Token ถูกลบแล้ว', 'success');
                    await this.loadSettings();
                } else {
                    this.showMessage('เกิดข้อผิดพลาดในการลบ Token', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการลบ Token', 'error');
            }
        },

        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.message = '';
            }, 5000);
        }
    }
}
</script>
@endpush
@endsection
