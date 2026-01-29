@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบดูดวง Facebook')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="fortuneSettings()">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            🔮 ตั้งค่าระบบดูดวง Facebook Messenger
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            จัดการการตั้งค่า Facebook App, AI Provider และการทำงานของระบบดูดวง
        </p>
    </div>

    <form action="{{ route('admin.fortune.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Status Card --}}
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">สถานะระบบ</h3>
                    <p class="text-blue-100">
                        @if($settings->is_enabled)
                            ✅ เปิดใช้งาน - พร้อมรับคำขอดูดวง
                        @else
                            ⛔ ปิดใช้งาน - ระบบไม่ตอบสนองคำขอ
                        @endif
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_enabled" value="1" 
                           {{ $settings->is_enabled ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
                </label>
            </div>
        </div>

        {{-- Facebook Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📘 การตั้งค่า Facebook
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        App ID
                    </label>
                    <input type="text" name="facebook_app_id" 
                           value="{{ old('facebook_app_id', $settings->facebook_app_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="1234567890123456">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        App Secret
                    </label>
                    <input type="password" name="facebook_app_secret" 
                           value="{{ old('facebook_app_secret', $settings->facebook_app_secret) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="••••••••••••••••">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Page ID
                    </label>
                    <input type="text" name="facebook_page_id" 
                           value="{{ old('facebook_page_id', $settings->facebook_page_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="1234567890">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Verify Token
                    </label>
                    <input type="text" name="facebook_verify_token" 
                           value="{{ old('facebook_verify_token', $settings->facebook_verify_token) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="your_verify_token_here">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Page Access Token
                    </label>
                    <textarea name="facebook_page_token" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="EAAxx...">{{ old('facebook_page_token', $settings->facebook_page_token) }}</textarea>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    💡 <strong>Webhook URL:</strong> <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ url('/webhook/facebook') }}</code>
                </p>
            </div>
        </div>

        {{-- AI Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    🤖 การตั้งค่า AI Provider
                </h3>
                <button type="button" @click="testAI()" 
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    ทดสอบการเชื่อมต่อ
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        AI Provider
                    </label>
                    <select name="ai_provider" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="gemini" {{ $settings->ai_provider === 'gemini' ? 'selected' : '' }}>Gemini (Google) - แนะนำ</option>
                        <option value="groq" {{ $settings->ai_provider === 'groq' ? 'selected' : '' }}>Groq - เร็วที่สุด</option>
                        <option value="qwen" {{ $settings->ai_provider === 'qwen' ? 'selected' : '' }}>Qwen (Alibaba)</option>
                        <option value="openrouter" {{ $settings->ai_provider === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Model
                    </label>
                    <input type="text" name="ai_model" 
                           value="{{ old('ai_model', $settings->ai_model) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="gemini-2.0-flash-exp">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Key
                    </label>
                    <input type="password" name="ai_api_key" 
                           value="{{ old('ai_api_key', $settings->ai_api_key) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="AIz...">
                </div>
            </div>
        </div>

        {{-- Usage Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                ⚙️ การตั้งค่าการใช้งาน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนฟรี/วัน
                    </label>
                    <input type="number" name="max_free_readings" min="0" max="100"
                           value="{{ old('max_free_readings', $settings->max_free_readings) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ราคา/ครั้ง (บาท)
                    </label>
                    <input type="number" name="reading_price" min="0" step="0.01"
                           value="{{ old('reading_price', $settings->reading_price) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        QR Code ชำระเงิน
                    </label>
                    <input type="file" name="payment_qr_image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-4 space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="respond_in_comment" value="1"
                           {{ $settings->respond_in_comment ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ตอบกลับในคอมเมนต์ (ไม่ใช่ส่ง private message)</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="require_registration" value="1"
                           {{ $settings->require_registration ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">บังคับให้สมัครสมาชิกก่อนใช้งาน</span>
                </label>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.readings.index') }}" 
               class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                ดูประวัติการทำนาย
            </a>
            <button type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function fortuneSettings() {
    return {
        async testAI() {
            try {
                const response = await fetch('{{ route("admin.fortune.settings.test-ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            }
        }
    };
}
</script>
@endpush
@endsection
