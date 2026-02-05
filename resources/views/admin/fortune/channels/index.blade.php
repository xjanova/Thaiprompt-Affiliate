@extends('layouts.admin')

@section('title', 'จัดการช่องทางดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="fortuneChannels()">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            📱 จัดการช่องทางรับส่งข้อความ
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            เชื่อมต่อช่องทางต่างๆ เพื่อรับคำถามดูดวงจากผู้ใช้ ปัจจุบันรองรับ Facebook Messenger และ LINE Official Account
        </p>
    </div>

    {{-- สถิติภาพรวม --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {{-- Facebook Stats Card --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fab fa-facebook-messenger text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Facebook Messenger</h3>
                        <p class="text-blue-100 text-sm">
                            @if($settings->hasFacebookConfigured())
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                    เชื่อมต่อแล้ว
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                                    ยังไม่ได้ตั้งค่า
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ number_format($stats['facebook']['total']) }}</div>
                    <div class="text-blue-100 text-xs">คำทำนาย</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ number_format($stats['facebook']['unique_users']) }}</div>
                    <div class="text-blue-100 text-xs">ผู้ใช้</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold">฿{{ number_format($stats['facebook']['total_revenue'], 0) }}</div>
                    <div class="text-blue-100 text-xs">รายได้</div>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20">
                <a href="{{ route('admin.fortune.settings.index') }}"
                   class="inline-flex items-center gap-2 text-sm text-white hover:text-blue-200 transition">
                    <i class="fas fa-cog"></i>
                    ไปหน้าตั้งค่า Facebook
                </a>
            </div>
        </div>

        {{-- LINE Stats Card --}}
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fab fa-line text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">LINE Official Account</h3>
                        <p class="text-green-100 text-sm">
                            @if($settings->line_enabled && $settings->hasLineConfigured())
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></span>
                                    เปิดใช้งาน
                                </span>
                            @elseif($settings->hasLineConfigured())
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                                    ตั้งค่าแล้ว (ยังไม่เปิด)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 bg-gray-300 rounded-full"></span>
                                    ยังไม่ได้ตั้งค่า
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ number_format($stats['line']['total']) }}</div>
                    <div class="text-green-100 text-xs">คำทำนาย</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ number_format($stats['line']['unique_users']) }}</div>
                    <div class="text-green-100 text-xs">ผู้ใช้</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold">฿{{ number_format($stats['line']['total_revenue'], 0) }}</div>
                    <div class="text-green-100 text-xs">รายได้</div>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20 text-sm">
                <span class="text-green-200">
                    <i class="fas fa-magic mr-1"></i>
                    รองรับ Flex Message สวยงาม
                </span>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.fortune.channels.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- LINE Official Account Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fab fa-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            LINE Official Account
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            เชื่อมต่อ LINE Official Account เพื่อรับคำถามดูดวง
                        </p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="line_enabled" value="1"
                           {{ $settings->line_enabled ? 'checked' : '' }}
                           class="sr-only peer"
                           x-model="lineEnabled">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
                </label>
            </div>

            {{-- Webhook URL Info --}}
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-start gap-3">
                    <i class="fas fa-link text-green-600 dark:text-green-400 mt-1"></i>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">
                            Webhook URL สำหรับ LINE Developers Console:
                        </p>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 bg-white dark:bg-gray-800 px-3 py-2 rounded border border-green-300 dark:border-green-700 text-sm text-gray-900 dark:text-gray-100"
                                  id="line-webhook-url">{{ url('/webhook/line/fortune') }}</code>
                            <button type="button" @click="copyWebhookUrl()"
                                    class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition text-sm">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LINE Channel Settings --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Channel ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="line_channel_id"
                           value="{{ old('line_channel_id', $settings->line_channel_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                           placeholder="1234567890">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        ดูได้ที่ LINE Developers Console → Basic settings
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Channel Secret <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="line_channel_secret"
                           value="{{ old('line_channel_secret', $settings->line_channel_secret) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                           placeholder="••••••••••••••••">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        ดูได้ที่ LINE Developers Console → Basic settings
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Channel Access Token (Long-lived) <span class="text-red-500">*</span>
                    </label>
                    <textarea name="line_channel_access_token" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                              placeholder="กด Issue ที่ Messaging API settings">{{ old('line_channel_access_token', $settings->line_channel_access_token) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        กด "Issue" ที่ LINE Developers Console → Messaging API → Channel access token
                    </p>
                </div>
            </div>

            {{-- Flex Message Customization --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-palette mr-2 text-purple-500"></i>
                    ปรับแต่ง Flex Message
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สีหลัก (Primary Color)
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="line_flex_primary_color"
                                   value="{{ old('line_flex_primary_color', $settings->line_flex_primary_color ?? '#6B46C1') }}"
                                   class="w-12 h-12 rounded cursor-pointer border-2 border-gray-300 dark:border-gray-600">
                            <input type="text" readonly
                                   :value="primaryColor"
                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            ใช้สำหรับปุ่มและส่วนเน้นใน Flex Message
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รูปภาพ Welcome Message (URL)
                        </label>
                        <input type="url" name="line_welcome_image_url"
                               value="{{ old('line_welcome_image_url', $settings->line_welcome_image_url) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                               placeholder="https://example.com/image.jpg">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            รูปภาพที่แสดงเมื่อผู้ใช้ Add Friend (แนะนำ: 1040x1040px)
                        </p>
                    </div>
                </div>
            </div>

            {{-- Test Connection Button --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                <button type="button" @click="testLineConnection()"
                        :disabled="testingLine"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg transition">
                    <i class="fas" :class="testingLine ? 'fa-spinner fa-spin' : 'fa-plug'"></i>
                    <span x-text="testingLine ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ LINE'"></span>
                </button>

                {{-- Test Result --}}
                <div x-show="testResult" x-cloak
                     class="mt-4 p-4 rounded-lg"
                     :class="testResult?.success ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                    <div class="flex items-start gap-3">
                        <i class="fas"
                           :class="testResult?.success ? 'fa-check-circle text-green-600 dark:text-green-400' : 'fa-exclamation-circle text-red-600 dark:text-red-400'"></i>
                        <div>
                            <p :class="testResult?.success ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                               x-text="testResult?.message"></p>
                            <div x-show="testResult?.data?.bot_info" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <p><strong>Bot Name:</strong> <span x-text="testResult?.data?.bot_info?.displayName"></span></p>
                                <p><strong>User ID:</strong> <span x-text="testResult?.data?.bot_info?.userId"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- วิธีตั้งค่า LINE --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-book mr-2 text-blue-500"></i>
                วิธีตั้งค่า LINE Official Account
            </h4>

            <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center font-bold">1</span>
                    <div>
                        <p class="font-medium">สมัคร LINE Official Account</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            ไปที่ <a href="https://manager.line.biz/" target="_blank" class="text-blue-600 hover:underline">LINE Official Account Manager</a> และสร้างบัญชีใหม่
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center font-bold">2</span>
                    <div>
                        <p class="font-medium">เปิดใช้งาน Messaging API</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            ไปที่ Settings → Messaging API → Enable Messaging API
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center font-bold">3</span>
                    <div>
                        <p class="font-medium">ดึงข้อมูล Channel จาก LINE Developers Console</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            ไปที่ <a href="https://developers.line.biz/console/" target="_blank" class="text-blue-600 hover:underline">LINE Developers Console</a> → เลือก Provider และ Channel
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center font-bold">4</span>
                    <div>
                        <p class="font-medium">ตั้งค่า Webhook URL</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            ที่ Messaging API settings → ใส่ Webhook URL: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ url('/webhook/line/fortune') }}</code>
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center font-bold">5</span>
                    <div>
                        <p class="font-medium">เปิด Use webhook และปิด Auto-reply</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            ที่ Messaging API settings → เปิด "Use webhook" และปิด "Auto-reply messages" ที่ LINE Official Account Manager
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.fortune.settings.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transition transform hover:scale-105">
                <i class="fas fa-save mr-2"></i>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function fortuneChannels() {
    return {
        lineEnabled: {{ $settings->line_enabled ? 'true' : 'false' }},
        primaryColor: '{{ $settings->line_flex_primary_color ?? '#6B46C1' }}',
        testingLine: false,
        testResult: null,

        init() {
            // Watch color input
            const colorInput = document.querySelector('input[name="line_flex_primary_color"]');
            if (colorInput) {
                colorInput.addEventListener('input', (e) => {
                    this.primaryColor = e.target.value;
                });
            }
        },

        copyWebhookUrl() {
            const url = document.getElementById('line-webhook-url').textContent;
            navigator.clipboard.writeText(url).then(() => {
                // แสดง toast หรือ feedback
                alert('คัดลอก URL แล้ว!');
            });
        },

        async testLineConnection() {
            this.testingLine = true;
            this.testResult = null;

            try {
                const response = await fetch('{{ route('admin.fortune.channels.test-line') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                this.testResult = data;

            } catch (error) {
                this.testResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message
                };
            } finally {
                this.testingLine = false;
            }
        }
    }
}
</script>
@endpush
@endsection
