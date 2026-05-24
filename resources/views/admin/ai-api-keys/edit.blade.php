@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div x-data="editApiKey()" class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.ai-api-keys.provider', $key->provider) }}"
           class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
    </div>

    {{-- Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form @submit.prevent="submitForm()" class="space-y-6">
            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ชื่อ Key <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="form.name" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Provider (readonly) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                <input type="text" value="{{ $key->provider_name }}" readonly
                       class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400">
            </div>

            {{-- 🎯 Model — per-key override --}}
            @if(!empty($modelsByProvider[$key->provider]))
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Model <span class="text-xs text-gray-500">(ค่า default = ตัวแรก)</span>
                </label>
                <select x-model="form.model"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- ใช้ค่า default ของ provider --</option>
                    @foreach($modelsByProvider[$key->provider] as $m)
                        <option value="{{ $m }}" {{ $key->model === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Default: <span class="font-mono text-blue-600 dark:text-blue-400">{{ $modelsByProvider[$key->provider][0] ?? '—' }}</span>
                </p>
            </div>
            @endif

            {{-- 🌐 Custom Base URL --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Custom Base URL <span class="text-xs text-gray-500">(เลือกใช้)</span>
                </label>
                <input type="url" x-model="form.base_url"
                       placeholder="{{ $defaultBaseUrls[$key->provider] ?? 'https://api.example.com/v1' }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Default: <span class="font-mono text-blue-600 dark:text-blue-400">{{ $defaultBaseUrls[$key->provider] ?? '—' }}</span>
                </p>
            </div>

            {{-- 🔴 Critical alert (ถ้า key ติด critical) --}}
            @if($key->is_critical)
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🔴</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-red-900 dark:text-red-100">Critical State</h4>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                            Key นี้ติด critical (3 strikes) — ระบบไม่ใช้อัตโนมัติ
                            ถ้าตรวจสอบแล้วใช้ได้ กดปุ่ม "ปลด Critical" เพื่อเปิดกลับ
                        </p>
                        @if($key->last_error)
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400 font-mono break-all">
                            Last error: {{ Str::limit($key->last_error, 200) }}
                        </p>
                        @endif
                        <button type="button" @click="clearCritical()"
                                class="mt-3 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition">
                            🔓 ปลด Critical
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- API Key --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
                <div class="relative">
                    <input :type="showApiKey ? 'text' : 'password'" x-model="form.api_key"
                           placeholder="ปล่อยว่างถ้าไม่ต้องการเปลี่ยน"
                           class="w-full px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" @click="showApiKey = !showApiKey"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg x-show="!showApiKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg x-show="showApiKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Key ปัจจุบัน: {{ $key->masked_key }}</p>
            </div>

            {{-- Active --}}
            <div class="flex items-center gap-3">
                <input type="checkbox" x-model="form.is_active" id="is_active"
                       class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                <input type="number" x-model="form.priority" min="0" max="100"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- 🎯 (2026-05-02) Purpose — จำกัดการใช้ key ตามวัตถุประสงค์ --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🎯 วัตถุประสงค์ (Purpose)
                </label>
                <select x-model="form.purpose"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    {{-- 🚫 (2026-05-24) ลบ 'any' ออกจาก PURPOSES — บังคับ admin เลือก purpose เจาะจง
                         key เก่าที่เคย purpose='any' จะแสดง blank — admin ต้องเลือกใหม่ก่อนบันทึก --}}
                    <option value="">— กรุณาเลือกวัตถุประสงค์ —</option>
                    @foreach(\App\Models\AiApiKey::PURPOSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if($key->purpose === 'any')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                    ⚠️ Key นี้ใช้ <code>purpose='any'</code> ซึ่งถูกยกเลิกแล้ว — Pool จะไม่เลือก key นี้
                    จนกว่าจะตั้งวัตถุประสงค์ใหม่
                </p>
                @endif
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    📋 <strong>Hierarchy การเลือก key</strong> (เจาะจง → ทั่วไป):<br>
                    💎 <strong>prediction_celtic</strong> → Celtic 99฿ ก่อน, fallback → prediction<br>
                    🌟 <strong>prediction_deep</strong> → Deep 39฿ ก่อน, fallback → prediction<br>
                    🔮 <strong>prediction</strong> = legacy — ใช้ทั้ง Deep + Celtic (ถ้าไม่อยากแยก)<br>
                    🎁 <strong>free_card</strong> → ทำนายฟรี (boost +1000 — สงวน paid keys)<br>
                    💬 <strong>chat</strong> → แชทสนทนา (FREE) — ไม่กระทบ pool ทำนาย<br>
                    <span class="text-blue-600 dark:text-blue-400">💙 <strong>chat_paid</strong> → แชทพิเศษ (สีฟ้า, paid) — LAST RESORT หลัง free หมด</span><br>
                    🌟 <strong>sensitive</strong> = STRICT — Pro Session (Bill / Celtic Premium) ไม่ fallback<br>
                    🎙️ <strong>tts</strong> = STRICT — เฉพาะสังเคราะห์เสียง<br>
                    🔼 <strong>Priority</strong> สูง = เลือกก่อนใน purpose เดียวกัน (100 = สูงสุด)
                </p>
            </div>

            {{-- Limits --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Daily Token Limit</label>
                    <input type="number" x-model="form.tokens_limit_daily" min="0"
                           placeholder="ว่าง = ไม่จำกัด"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Token Limit</label>
                    <input type="number" x-model="form.tokens_limit_monthly" min="0"
                           placeholder="ว่าง = ไม่จำกัด"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            {{-- Rate Limit --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rate Limit (requests/minute)</label>
                <input type="number" x-model="form.rate_limit_per_minute" min="0"
                       placeholder="ว่าง = ไม่จำกัด"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ</label>
                <textarea x-model="form.notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            {{-- Usage Stats --}}
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">สถิติการใช้งาน</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Tokens วันนี้</span>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($key->tokens_used_today) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Tokens เดือนนี้</span>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($key->tokens_used_month) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Tokens รวม</span>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($key->tokens_used_total) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Requests วันนี้</span>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($key->requests_today) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">RPM ปัจจุบัน (real-time)</span>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ number_format($key->current_rpm) }}
                            @if ($key->rate_limit_per_minute)
                                <span class="text-xs text-gray-500">/ {{ $key->rate_limit_per_minute }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">In-flight (concurrent)</span>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($key->current_inflight) }}</p>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" :disabled="submitting"
                        class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition flex items-center justify-center gap-2">
                    <svg x-show="submitting" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                </button>
                <a href="{{ route('admin.ai-api-keys.provider', $key->provider) }}"
                   class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function editApiKey() {
    return {
        showApiKey: false,
        submitting: false,
        form: {
            name: @json($key->name),
            model: @json($key->model ?? ''),
            base_url: @json($key->base_url ?? ''),
            api_key: '',
            is_active: {{ $key->is_active ? 'true' : 'false' }},
            priority: {{ $key->priority ?? 0 }},
            purpose: @json($key->purpose === 'any' ? '' : ($key->purpose ?? '')),
            tokens_limit_daily: {{ $key->tokens_limit_daily ?? 'null' }},
            tokens_limit_monthly: {{ $key->tokens_limit_monthly ?? 'null' }},
            rate_limit_per_minute: {{ $key->rate_limit_per_minute ?? 'null' }},
            notes: @json($key->notes ?? '')
        },

        async clearCritical() {
            if (!confirm('ปลด critical state? Key จะกลับมาใช้งานได้')) return;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.clear-critical', $key->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (e) {
                alert('เกิดข้อผิดพลาด: ' + e.message);
            }
        },

        async submitForm() {
            if (!this.form.name) {
                alert('กรุณากรอกชื่อ Key');
                return;
            }

            this.submitting = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.update', $key->id) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = '{{ route('admin.ai-api-keys.provider', $key->provider) }}';
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
