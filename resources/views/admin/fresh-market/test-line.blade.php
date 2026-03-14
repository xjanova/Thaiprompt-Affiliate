@extends('layouts.admin-v3')

@section('title', 'ทดสอบ LINE - ตลาดสดไทยพร๊อม')

@section('content')
<div class="space-y-6" x-data="{
    sending: false,
    verifying: false,
    botInfo: null,
    verifyError: null,
    async verifyConnection() {
        this.verifying = true;
        this.botInfo = null;
        this.verifyError = null;
        try {
            const res = await fetch('{{ route('admin.fresh-market.verify-line') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();
            if (data.success) {
                this.botInfo = data.data;
            } else {
                this.verifyError = data.error;
            }
        } catch (e) {
            this.verifyError = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์: ' + e.message;
        } finally {
            this.verifying = false;
        }
    }
}">
    {{-- ส่วนหัว --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ทดสอบส่งข้อความ LINE - ตลาดสดไทยพร๊อม</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ทดสอบการส่งข้อความผ่าน LINE OA ของตลาดสด</p>
        </div>
        <a href="{{ route('admin.fresh-market.settings') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <i class="fas fa-cog mr-2"></i> ตั้งค่า LINE OA
        </a>
    </div>

    {{-- แจ้งเตือน --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- สถานะการเชื่อมต่อ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สถานะการเชื่อมต่อ LINE OA</h3>
            <button @click="verifyConnection()"
                    :disabled="verifying"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-xl transition">
                <template x-if="!verifying">
                    <span class="flex items-center">
                        <i class="fas fa-plug mr-2"></i> ตรวจสอบการเชื่อมต่อ
                    </span>
                </template>
                <template x-if="verifying">
                    <span class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        กำลังตรวจสอบ...
                    </span>
                </template>
            </button>
        </div>

        {{-- Config status indicators --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="w-3 h-3 rounded-full {{ ($settings->line_channel_id ?? '') ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Channel ID</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ ($settings->line_channel_id ?? '') ? 'กำหนดค่าแล้ว' : 'ยังไม่ได้กำหนดค่า' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="w-3 h-3 rounded-full {{ ($settings->line_channel_secret ?? '') ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Channel Secret</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ ($settings->line_channel_secret ?? '') ? 'กำหนดค่าแล้ว' : 'ยังไม่ได้กำหนดค่า' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="w-3 h-3 rounded-full {{ ($settings->line_channel_access_token ?? '') ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Access Token</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ ($settings->line_channel_access_token ?? '') ? 'กำหนดค่าแล้ว' : 'ยังไม่ได้กำหนดค่า' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ผลการตรวจสอบ --}}
        <template x-if="botInfo">
            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-xl">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <p class="text-green-700 dark:text-green-400 font-semibold">เชื่อมต่อ LINE OA สำเร็จ!</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center gap-2" x-show="botInfo.displayName">
                        <span class="text-gray-500 dark:text-gray-400">ชื่อ Bot:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="botInfo.displayName"></span>
                    </div>
                    <div class="flex items-center gap-2" x-show="botInfo.basicId">
                        <span class="text-gray-500 dark:text-gray-400">Basic ID:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="'@' + botInfo.basicId"></span>
                    </div>
                    <div class="flex items-center gap-2" x-show="botInfo.userId">
                        <span class="text-gray-500 dark:text-gray-400">Bot User ID:</span>
                        <span class="font-mono text-xs text-gray-900 dark:text-white" x-text="botInfo.userId"></span>
                    </div>
                    <div class="flex items-center gap-2" x-show="botInfo.chatMode">
                        <span class="text-gray-500 dark:text-gray-400">Chat Mode:</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="botInfo.chatMode"></span>
                    </div>
                </div>
                <template x-if="botInfo.pictureUrl">
                    <div class="mt-3 flex items-center gap-3">
                        <img :src="botInfo.pictureUrl" alt="Bot Avatar" class="w-12 h-12 rounded-full">
                        <span class="text-sm text-gray-500 dark:text-gray-400">รูปโปรไฟล์ Bot</span>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="verifyError">
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-xl">
                <div class="flex items-start gap-3">
                    <i class="fas fa-times-circle text-red-500 text-xl mt-0.5"></i>
                    <div>
                        <p class="text-red-700 dark:text-red-400 font-semibold">เชื่อมต่อไม่สำเร็จ</p>
                        <p class="text-red-600 dark:text-red-300 text-sm mt-1" x-text="verifyError"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ฟอร์มทดสอบ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ส่งข้อความทดสอบ</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กรอก User ID ของ LINE และข้อความที่ต้องการส่งเพื่อทดสอบการเชื่อมต่อ</p>

        <form method="POST" action="{{ route('admin.fresh-market.test-line.send') }}"
              @submit="sending = true">
            @csrf

            <div class="space-y-4 max-w-2xl">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        LINE User ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="user_id" value="{{ old('user_id') }}" required
                           class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                           placeholder="เช่น U1234567890abcdef...">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        LINE User ID เริ่มต้นด้วย "U" ตามด้วยตัวอักษรและตัวเลข 32 ตัว
                        — ดูจาก Webhook event เมื่อมีคน follow หรือส่งข้อความมา
                    </p>
                    @error('user_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความ <span class="text-red-500">*</span>
                    </label>
                    <textarea name="message" rows="5" required
                              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                              placeholder="พิมพ์ข้อความที่ต้องการส่งทดสอบ...">{{ old('message', 'ทดสอบการส่งข้อความจากระบบตลาดสดไทยพร๊อม') }}</textarea>
                    @error('message')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit"
                            :disabled="sending"
                            class="inline-flex items-center px-8 py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        <template x-if="!sending">
                            <span class="flex items-center">
                                <i class="fab fa-line mr-2 text-lg"></i> ส่งข้อความทดสอบ
                            </span>
                        </template>
                        <template x-if="sending">
                            <span class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                กำลังส่ง...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- คำแนะนำ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>คำแนะนำ
        </h3>
        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-start gap-2">
                <span class="text-green-500 mt-0.5"><i class="fas fa-check-circle"></i></span>
                <p>กดปุ่ม <strong>"ตรวจสอบการเชื่อมต่อ"</strong> ด้านบนเพื่อยืนยันว่า Access Token ถูกต้องและ Bot พร้อมใช้งาน</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-green-500 mt-0.5"><i class="fas fa-check-circle"></i></span>
                <p>ตรวจสอบว่าได้กำหนดค่า Channel ID, Channel Secret และ Access Token ที่หน้า <a href="{{ route('admin.fresh-market.settings') }}" class="text-green-600 dark:text-green-400 underline">ตั้งค่าระบบ</a> แล้ว</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-green-500 mt-0.5"><i class="fas fa-check-circle"></i></span>
                <p>User ID สามารถดูได้จาก LINE Webhook event เมื่อผู้ใช้ follow หรือส่งข้อความมา</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-green-500 mt-0.5"><i class="fas fa-check-circle"></i></span>
                <p>ผู้รับข้อความต้องเป็นเพื่อนกับ LINE OA ของระบบตลาดสดจึงจะได้รับข้อความ</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-yellow-500 mt-0.5"><i class="fas fa-exclamation-triangle"></i></span>
                <p>การส่งข้อความ push จะถูกนับเป็นจำนวนข้อความรายเดือนของ LINE OA ตามแพลนที่ใช้</p>
            </div>
        </div>
    </div>
</div>
@endsection
