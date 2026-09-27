@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า Google Login (OAuth)')

@section('content')
<div class="container-fluid px-4 py-6" x-data="googleOAuthPage()">

    {{-- Hero Header --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-gray-950 p-8 shadow-2xl">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#4285F4] via-[#EA4335] via-50% to-[#34A853]"></div>
        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-white flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8" viewBox="0 0 18 18" aria-hidden="true">
                        <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                        <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/>
                        <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/>
                        <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">Google Login (OAuth)</h1>
                    <p class="text-slate-300 text-sm">ให้ลูกค้าเข้าสู่ระบบด้วยบัญชี Google — ใช้ได้ทั้งเว็บและแอป Thai Prompt</p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-white text-center">
                    <div class="text-sm font-bold">
                        @if($isReady)
                            <span class="text-green-300">● เปิดใช้งาน</span>
                        @else
                            <span class="text-slate-300">○ ยังไม่เปิด</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-300">สถานะ</div>
                </div>
                <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-white text-center">
                    <div class="text-2xl font-bold">{{ number_format($settings->total_logins ?? 0) }}</div>
                    <div class="text-xs text-slate-300">login ทั้งหมด</div>
                </div>
                <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-white text-center">
                    <div class="text-sm font-bold">
                        {{ $settings->last_login_at ? $settings->last_login_at->diffForHumans() : '-' }}
                    </div>
                    <div class="text-xs text-slate-300">ล่าสุด</div>
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

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl mt-0.5"></i>
                <ul class="text-red-800 dark:text-red-300 text-sm space-y-1">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Setup Guide Card --}}
    <div class="mb-6 p-5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-amber-600 dark:text-amber-400 text-xl mt-1"></i>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-amber-900 dark:text-amber-200 mb-2">📋 วิธีตั้งค่า (ทำครั้งเดียว ประมาณ 5 นาที)</h3>
                <ol class="text-sm text-amber-800 dark:text-amber-300 space-y-2 list-decimal pl-4">
                    <li>เข้า <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">console.cloud.google.com → APIs &amp; Services → Credentials</a> (เลือกหรือสร้างโปรเจกต์)</li>
                    <li>ครั้งแรกต้องตั้ง <strong>OAuth consent screen</strong> ก่อน: เลือก <strong>External</strong> → ใส่ชื่อแอป "Thai Prompt" + อีเมลติดต่อ → โดเมน <code class="px-1 bg-amber-100 dark:bg-amber-900/40 rounded">thaiprompt.online</code> → กด <strong>Publish app</strong> (ไม่งั้นล็อกอินได้เฉพาะอีเมลทดสอบ)</li>
                    <li>กด <strong>Create credentials → OAuth client ID</strong> → Application type เลือก <strong>Web application</strong></li>
                    <li>ช่อง <strong>Authorized redirect URIs</strong> กด Add URI แล้ววางค่านี้ (ต้องตรงทุกตัวอักษร):
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <code class="px-2 py-1 bg-amber-100 dark:bg-amber-900/40 rounded text-xs select-all break-all">{{ $redirectUri }}</code>
                            <button type="button" @click="copy(@js($redirectUri), $event)"
                                    class="px-2 py-1 text-xs bg-amber-600 hover:bg-amber-700 text-white rounded transition">
                                📋 คัดลอก
                            </button>
                        </div>
                    </li>
                    <li>กด Create → คัดลอก <strong>Client ID</strong> กับ <strong>Client secret</strong> มาวางในฟอร์มด้านล่าง → เปิด "เปิดใช้งาน" → บันทึก</li>
                    <li>เสร็จแล้วปุ่ม "เข้าสู่ระบบด้วย Google" จะแสดงเองที่หน้า /login, /register และในแอป (แอปไม่ต้องอัปเดตค่าใดๆ เพิ่ม)</li>
                </ol>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.auth.google-oauth.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Toggle --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">⚡ เปิดใช้งาน Google Login</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        เปิดแล้ว → ปุ่ม "เข้าสู่ระบบด้วย Google" แสดงบนเว็บและในแอป · ปิด = ซ่อนทุกที่
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $settings->is_active))
                           class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>

        {{-- Credentials --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 space-y-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-key text-blue-600"></i>
                ค่าจาก Google Cloud (OAuth client แบบ Web application)
            </h3>

            {{-- Client ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client ID</label>
                <input type="text" name="client_id" autocomplete="off" spellcheck="false"
                       value="{{ old('client_id', $settings->client_id) }}"
                       placeholder="เช่น 123456789012-abcdefg.apps.googleusercontent.com"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ลงท้ายด้วย .apps.googleusercontent.com</p>
            </div>

            {{-- Client secret (ไม่เคยแสดงค่าเดิม) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client secret
                    @if($hasSecret)
                        <span class="text-xs text-green-600 dark:text-green-400 ml-2">✓ บันทึกแล้ว (ปล่อยว่างเพื่อคงค่าเดิม)</span>
                    @endif
                </label>
                <div class="relative">
                    <input :type="showSecret ? 'text' : 'password'" name="client_secret" autocomplete="new-password" spellcheck="false"
                           placeholder="{{ $hasSecret ? '••••••••••••••••' : 'วาง Client secret จาก Google Cloud' }}"
                           class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                    <button type="button" @click="showSecret = !showSecret"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            :aria-label="showSecret ? 'ซ่อน' : 'แสดง'">
                        <i :class="showSecret ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    ⚠️ เป็นความลับ — เก็บแบบเข้ารหัสในฐานข้อมูล และจะไม่แสดงค่าเดิมบนหน้านี้อีก
                </p>
            </div>

            {{-- Redirect URI (อ่านอย่างเดียว) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Redirect URI (วางใน Google Cloud)</label>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="text" readonly value="{{ $redirectUri }}"
                           class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-mono text-sm">
                    <button type="button" @click="copy(@js($redirectUri), $event)"
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 text-sm font-semibold rounded-lg transition">
                        📋 คัดลอก
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ใช้ URI นี้ตัวเดียวทั้งเว็บและแอป (แอปเปิดหน้าเว็บเข้าสู่ระบบแล้วพากลับแอปเอง)
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <button type="button" @click="testConfig()" :disabled="testing"
                    class="px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-semibold rounded-xl transition shadow-lg">
                <i class="fas fa-plug mr-2"></i>
                <span x-show="!testing">ตรวจการตั้งค่า</span>
                <span x-show="testing" x-cloak>กำลังตรวจ...</span>
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
                <p class="font-semibold" :class="testResult?.success ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'"
                   x-text="testResult?.message"></p>
            </div>
        </div>
    </form>
</div>

<script>
function googleOAuthPage() {
    return {
        showSecret: false,
        testing: false,
        testResult: null,

        // คัดลอก redirect URI
        copy(text, event) {
            const button = event.currentTarget;
            navigator.clipboard.writeText(text).then(() => {
                button.innerText = '✓ คัดลอกแล้ว';
            }).catch(() => {
                button.innerText = 'คัดลอกไม่ได้ — เลือกข้อความเอง';
            });
        },

        // ตรวจว่าตั้งค่าครบ (ไม่ยิงไป Google)
        testConfig() {
            this.testing = true;
            this.testResult = null;

            fetch(@js(route('admin.auth.google-oauth.test')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @js(csrf_token()),
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => { this.testResult = data; })
            .catch(() => { this.testResult = { success: false, message: 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ ลองใหม่อีกครั้ง' }; })
            .finally(() => { this.testing = false; });
        },
    };
}
</script>
@endsection
