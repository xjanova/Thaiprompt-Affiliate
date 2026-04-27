@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า Facebook Login (OAuth)')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    isEnabled: {{ old('is_enabled', $settings->is_enabled) ? 'true' : 'false' }},
    showSecret: false,
    testing: false,
    testResult: null,
}">

    {{-- Hero Header (FB blue gradient) --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center border border-white/20">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">Facebook Login (OAuth)</h1>
                    <p class="text-blue-100 text-sm">ตั้งค่าให้ลูกค้า login ด้วย Facebook → เข้าหน้า wallet/รายได้</p>
                </div>
            </div>

            {{-- สถิติ login --}}
            <div class="flex gap-3">
                <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-white text-center">
                    <div class="text-2xl font-bold">{{ number_format($settings->total_logins ?? 0) }}</div>
                    <div class="text-xs text-blue-100">login ทั้งหมด</div>
                </div>
                <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-white text-center">
                    <div class="text-sm font-bold">
                        {{ $settings->last_login_at ? $settings->last_login_at->diffForHumans() : '-' }}
                    </div>
                    <div class="text-xs text-blue-100">ล่าสุด</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 flex items-center gap-3">
            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
            <p class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Setup Guide Card --}}
    <div class="mb-6 p-5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-amber-600 dark:text-amber-400 text-xl mt-1"></i>
            <div class="flex-1">
                <h3 class="font-bold text-amber-900 dark:text-amber-200 mb-2">📋 วิธีตั้งค่า Facebook App</h3>
                <ol class="text-sm text-amber-800 dark:text-amber-300 space-y-1 list-decimal pl-4">
                    <li>ไปที่ <a href="https://developers.facebook.com/apps/" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">developers.facebook.com/apps</a> → สร้าง App หรือเลือก App ที่มี</li>
                    <li>เพิ่ม <strong>Facebook Login</strong> product → เปิด Web platform</li>
                    <li>คัดลอก <strong>App ID</strong> + <strong>App Secret</strong> จาก Settings → Basic มาใส่ในฟอร์มด้านล่าง</li>
                    <li>เพิ่ม <strong>Valid OAuth Redirect URIs</strong> ใน FB Console:<br>
                        <code class="px-2 py-1 bg-amber-100 dark:bg-amber-900/40 rounded text-xs select-all">{{ $callbackRouteUrl }}</code>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $callbackRouteUrl }}'); this.innerText='✓ คัดลอกแล้ว'"
                                class="ml-2 px-2 py-1 text-xs bg-amber-600 hover:bg-amber-700 text-white rounded transition">
                            📋 คัดลอก
                        </button>
                    </li>
                    <li>เปิด toggle "เปิดใช้งาน" → บันทึก → ลูกค้าจะเห็นปุ่ม "เข้าสู่ระบบด้วย Facebook" บนหน้า /login</li>
                </ol>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.auth.facebook-oauth.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Toggle: Enable Facebook Login --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">⚡ เปิดใช้งาน Facebook Login</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        เมื่อเปิด → ปุ่ม "เข้าสู่ระบบด้วย Facebook" จะแสดงบนหน้า /login
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_enabled" value="0">
                    <input type="checkbox" name="is_enabled" value="1"
                           x-model="isEnabled"
                           class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>

        {{-- Credentials Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-key text-blue-600"></i>
                Credentials จาก Facebook Developer Console
            </h3>

            {{-- App ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    App ID (Client ID)
                </label>
                <input type="text" name="app_id"
                       value="{{ old('app_id', $settings->app_id) }}"
                       placeholder="เช่น 664172615253513"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตัวเลข ID ของ FB App</p>
            </div>

            {{-- App Secret (with show/hide) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    App Secret
                    @if($settings->app_secret)
                        <span class="text-xs text-green-600 dark:text-green-400 ml-2">✓ บันทึกแล้ว (ปล่อยว่างเพื่อคงค่าเดิม)</span>
                    @endif
                </label>
                <div class="relative">
                    <input :type="showSecret ? 'text' : 'password'" name="app_secret"
                           placeholder="{{ $settings->app_secret ? '••••••••••••••••' : 'กรอก App Secret จาก FB Console' }}"
                           class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                    <button type="button" @click="showSecret = !showSecret"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <i :class="showSecret ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    ⚠️ App Secret เป็นความลับ — จัดเก็บแบบเข้ารหัสในฐานข้อมูล อย่าแชร์กับคนอื่น
                </p>
            </div>

            {{-- Redirect URI --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Redirect URI (Callback URL)
                </label>
                <input type="text" name="redirect_uri"
                       value="{{ old('redirect_uri', $settings->redirect_uri ?? $suggestedRedirectUri) }}"
                       placeholder="{{ $suggestedRedirectUri }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ต้องตรงกับ "Valid OAuth Redirect URIs" ใน FB Console เป๊ะๆ — แนะนำใช้:
                    <code class="text-blue-600 dark:text-blue-400">{{ $suggestedRedirectUri }}</code>
                </p>
            </div>
        </div>

        {{-- Advanced Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-cog text-purple-600"></i>
                ตัวเลือกขั้นสูง
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Graph API Version --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Graph API Version
                    </label>
                    <select name="graph_version"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        @foreach (['v18.0', 'v19.0', 'v20.0', 'v21.0'] as $version)
                            <option value="{{ $version }}" @selected(old('graph_version', $settings->graph_version) === $version)>{{ $version }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Scopes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        OAuth Scopes (1 ตัวต่อบรรทัด)
                    </label>
                    <textarea name="scopes" rows="2"
                              placeholder="email&#10;public_profile"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm">{{ old('scopes', is_array($settings->scopes) ? implode("\n", $settings->scopes) : "email\npublic_profile") }}</textarea>
                </div>
            </div>

            {{-- Auto-create options --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">เมื่อมี user ใหม่ login ผ่าน FB:</p>

                @php
                    $autoOptions = [
                        ['key' => 'auto_create_user', 'label' => '✅ สร้าง User account อัตโนมัติ', 'desc' => 'ถ้าไม่มีในระบบ → สร้างใหม่จาก FB profile'],
                        ['key' => 'auto_create_wallet', 'label' => '💎 สร้าง Wallet อัตโนมัติ', 'desc' => 'รองรับการรับคอมมิชชั่นทันที'],
                        ['key' => 'auto_create_mlm_member', 'label' => '🌳 สร้าง MlmMember อัตโนมัติ', 'desc' => 'ผูกสาย sponsor (default: Super Admin) — เข้าระบบ Affiliate'],
                    ];
                @endphp

                @foreach ($autoOptions as $opt)
                    <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                        <input type="hidden" name="{{ $opt['key'] }}" value="0">
                        <input type="checkbox" name="{{ $opt['key'] }}" value="1"
                               @checked(old($opt['key'], $settings->{$opt['key']} ?? true))
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $opt['label'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $opt['desc'] }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-3">
            <button type="button" @click="testConfig()" :disabled="testing"
                    class="px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-semibold rounded-xl transition shadow-lg">
                <i class="fas fa-plug mr-2"></i>
                <span x-show="!testing">ทดสอบการตั้งค่า</span>
                <span x-show="testing">กำลังทดสอบ...</span>
            </button>

            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-semibold rounded-xl transition shadow-lg">
                <i class="fas fa-save mr-2"></i>
                บันทึกการตั้งค่า
            </button>
        </div>

        {{-- Test result --}}
        <div x-show="testResult" x-cloak x-transition
             :class="testResult?.success ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700'"
             class="p-4 rounded-xl border">
            <div class="flex items-start gap-3">
                <i :class="testResult?.success ? 'fas fa-check-circle text-green-600' : 'fas fa-exclamation-triangle text-red-600'" class="text-xl mt-1"></i>
                <div class="flex-1">
                    <p class="font-semibold" :class="testResult?.success ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'"
                       x-text="testResult?.message"></p>
                    <template x-if="testResult?.success">
                        <div class="mt-2 text-xs space-y-1 text-green-800 dark:text-green-300">
                            <div>↗️ Redirect URI: <code x-text="testResult?.redirect_uri" class="bg-green-100 dark:bg-green-900/40 px-1 rounded"></code></div>
                            <div>📋 Scopes: <code x-text="testResult?.scopes?.join(', ')" class="bg-green-100 dark:bg-green-900/40 px-1 rounded"></code></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function testConfig() {
    const wrapper = Alpine.$data(document.querySelector('[x-data]'));
    wrapper.testing = true;
    wrapper.testResult = null;

    fetch('{{ route("admin.auth.facebook-oauth.test") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        wrapper.testResult = data;
        wrapper.testing = false;
    })
    .catch(err => {
        wrapper.testResult = { success: false, message: 'Network error: ' + err.message };
        wrapper.testing = false;
    });
}
</script>
@endsection
