@extends('layouts.admin')

@section('title', 'ตั้งค่า LINE Official Account')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ตั้งค่า LINE Official Account</h1>
                <p class="mt-1 text-sm text-gray-600">จัดการการเชื่อมต่อกับ LINE OA และระบบ LINE Login</p>
            </div>
            <div class="flex items-center space-x-2">
                @if($settings->is_active)
                    <span class="px-3 py-1 text-sm font-semibold text-green-800 bg-green-100 rounded-full">เปิดใช้งาน</span>
                @else
                    <span class="px-3 py-1 text-sm font-semibold text-gray-800 bg-gray-100 rounded-full">ปิดใช้งาน</span>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.line-oa.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- LINE Channel Configuration -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่า LINE Channel</h2>
            <div class="space-y-4">
                <div>
                    <label for="channel_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Channel ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="channel_id" id="channel_id"
                           value="{{ old('channel_id', $settings->channel_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="1234567890">
                    <p class="mt-1 text-sm text-gray-500">Channel ID จาก LINE Developers Console</p>
                </div>

                <div>
                    <label for="channel_secret" class="block text-sm font-medium text-gray-700 mb-2">
                        Channel Secret <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="channel_secret" id="channel_secret"
                           value="{{ old('channel_secret', $settings->channel_secret) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="••••••••••••••••">
                    <p class="mt-1 text-sm text-gray-500">Channel Secret จาก LINE Developers Console</p>
                </div>

                <div>
                    <label for="channel_access_token" class="block text-sm font-medium text-gray-700 mb-2">
                        Channel Access Token (Long-lived)
                    </label>
                    <textarea name="channel_access_token" id="channel_access_token" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="Enter your channel access token here">{{ old('channel_access_token', $settings->channel_access_token) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">ใช้สำหรับส่งข้อความผ่าน Messaging API</p>
                </div>

                <div>
                    <label for="liff_id" class="block text-sm font-medium text-gray-700 mb-2">
                        LIFF ID (Optional)
                    </label>
                    <input type="text" name="liff_id" id="liff_id"
                           value="{{ old('liff_id', $settings->liff_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="1234567890-abcdefgh">
                    <p class="mt-1 text-sm text-gray-500">LIFF ID สำหรับใช้งาน LIFF (LINE Front-end Framework)</p>
                </div>
            </div>
        </div>

        <!-- Registration Settings -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่าการสมัครสมาชิก</h2>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="require_line_registration" id="require_line_registration"
                           value="1" {{ old('require_line_registration', $settings->require_line_registration) ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="require_line_registration" class="ml-2 block text-sm text-gray-900">
                        บังคับให้เข้าสู่ระบบด้วย LINE ก่อนสมัครสมาชิก
                    </label>
                </div>
                <p class="text-sm text-gray-500 ml-6">เมื่อเปิดใช้งาน ผู้ใช้จะต้องเข้าสู่ระบบด้วย LINE OA ก่อนจึงจะสมัครสมาชิกได้ (KYC Level 1)</p>
            </div>
        </div>

        <!-- Messaging Settings -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่าการส่งข้อความ</h2>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="enable_line_messaging" id="enable_line_messaging"
                           value="1" {{ old('enable_line_messaging', $settings->enable_line_messaging) ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="enable_line_messaging" class="ml-2 block text-sm text-gray-900">
                        เปิดใช้งานการส่งข้อความผ่าน LINE
                    </label>
                </div>

                <div>
                    <label for="welcome_message" class="block text-sm font-medium text-gray-700 mb-2">
                        ข้อความต้อนรับ (เมื่อเพิ่มเพื่อน)
                    </label>
                    <textarea name="welcome_message" id="welcome_message" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="ยินดีต้อนรับสู่ระบบ Affiliate!">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                </div>

                <div>
                    <label for="registration_success_message" class="block text-sm font-medium text-gray-700 mb-2">
                        ข้อความเมื่อสมัครสมาชิกสำเร็จ
                    </label>
                    <textarea name="registration_success_message" id="registration_success_message" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="🎉 สมัครสมาชิกสำเร็จ!">{{ old('registration_success_message', $settings->registration_success_message) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">ใช้ได้: {name}, {email}, {referral_code}</p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">สถานะการใช้งาน</h2>
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active"
                       value="1" {{ old('is_active', $settings->is_active) ? 'checked' : '' }}
                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    เปิดใช้งานระบบ LINE OA
                </label>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    <!-- Setup Instructions -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">คำแนะนำการตั้งค่า LINE Developers</h3>
        <div class="prose prose-sm text-blue-800 space-y-2">
            <ol class="list-decimal list-inside space-y-2">
                <li>เข้าสู่ <a href="https://developers.line.biz/console/" target="_blank" class="underline">LINE Developers Console</a></li>
                <li>สร้าง Provider หรือเลือก Provider ที่มีอยู่</li>
                <li>สร้าง Channel ใหม่ ประเภท "LINE Login"</li>
                <li>ในหน้า Channel settings:
                    <ul class="list-disc list-inside ml-5 mt-1">
                        <li>คัดลอก <strong>Channel ID</strong> และ <strong>Channel secret</strong></li>
                        <li>ตั้งค่า <strong>Callback URL</strong> เป็น: <code class="bg-white px-2 py-1 rounded">{{ route('line.callback') }}</code></li>
                    </ul>
                </li>
                <li>สำหรับ Messaging API (ถ้าต้องการส่งข้อความ):
                    <ul class="list-disc list-inside ml-5 mt-1">
                        <li>ไปที่แท็บ "Messaging API"</li>
                        <li>คัดลอก <strong>Channel access token</strong> (แบบ long-lived)</li>
                        <li>ตั้งค่า <strong>Webhook URL</strong> เป็น: <code class="bg-white px-2 py-1 rounded">{{ route('line.webhook') }}</code></li>
                        <li>เปิดใช้งาน "Use webhook"</li>
                    </ul>
                </li>
                <li>เปิดใช้งาน Bot prompt: "Aggressive" (บังคับให้เพิ่มเพื่อนเมื่อ login)</li>
            </ol>
        </div>
    </div>

    <!-- Test Message -->
    <div class="bg-white rounded-lg shadow-md p-6" x-data="{ showTest: false }">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">ทดสอบการส่งข้อความ</h2>
        <button @click="showTest = !showTest" type="button"
                class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
            <span x-show="!showTest">แสดงฟอร์มทดสอบ</span>
            <span x-show="showTest">ซ่อนฟอร์มทดสอบ</span>
        </button>

        <form x-show="showTest" x-transition method="POST" action="{{ route('admin.line-oa.test-message') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="line_user_id" class="block text-sm font-medium text-gray-700 mb-2">LINE User ID</label>
                <input type="text" name="line_user_id" id="line_user_id"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       placeholder="U1234567890abcdef1234567890abcdef" required>
                <p class="mt-1 text-sm text-gray-500">LINE User ID ของผู้ใช้ที่ต้องการทดสอบส่งข้อความ</p>
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">ข้อความทดสอบ</label>
                <textarea name="message" id="message" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                          placeholder="ข้อความทดสอบ..." required>ทดสอบการส่งข้อความจากระบบ</textarea>
            </div>

            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                ส่งข้อความทดสอบ
            </button>
        </form>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">ลิงก์ที่เป็นประโยชน์</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.line-oa.logs') }}" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <h3 class="font-semibold text-gray-900">ประวัติการใช้งาน LINE</h3>
                <p class="text-sm text-gray-600 mt-1">ดู logs การ login และการใช้งาน</p>
            </a>

            <a href="https://developers.line.biz/console/" target="_blank" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <h3 class="font-semibold text-gray-900">LINE Developers Console</h3>
                <p class="text-sm text-gray-600 mt-1">จัดการ LINE Channel</p>
            </a>

            <a href="https://developers.line.biz/en/docs/line-login/" target="_blank" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <h3 class="font-semibold text-gray-900">เอกสาร LINE Login</h3>
                <p class="text-sm text-gray-600 mt-1">คู่มือการใช้งาน LINE Login API</p>
            </a>
        </div>
    </div>
</div>
@endsection
