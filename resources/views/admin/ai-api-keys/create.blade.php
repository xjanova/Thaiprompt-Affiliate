@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div x-data="createApiKey()" class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.ai-api-keys.index') }}"
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
                       placeholder="เช่น Grok Key #1, Production Key"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Provider --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Provider <span class="text-red-500">*</span>
                </label>
                <select x-model="form.provider" @change="onProviderChange()" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- เลือก Provider --</option>
                    @foreach($providers as $key => $name)
                    <option value="{{ $key }}" {{ $provider === $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Model — cascading dropdown ตาม provider --}}
            <div x-show="form.provider && availableModels.length > 0" x-cloak>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Model <span class="text-xs text-gray-500">(ค่า default ของ provider จะใช้ตัวแรก)</span>
                </label>
                <select x-model="form.model"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- ใช้ค่า default ของ provider --</option>
                    <template x-for="m in availableModels" :key="m">
                        <option :value="m" x-text="m"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    เลือก model ที่จะใช้กับ key นี้ — ถ้าไม่เลือก จะใช้ตัวแรก:
                    <span class="font-mono text-blue-600 dark:text-blue-400" x-text="availableModels[0] || '—'"></span>
                </p>
            </div>

            {{-- Base URL — สำหรับ provider ที่มี endpoint ต่างกัน (Xiaomi, custom proxy) --}}
            <div x-show="form.provider" x-cloak>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Custom Base URL
                    <span class="text-xs text-gray-500">(เลือกใช้ — ปล่อยว่างเพื่อใช้ default)</span>
                </label>
                <input type="url" x-model="form.base_url"
                       :placeholder="defaultBaseUrl || 'https://api.example.com/v1'"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Default ของ provider:
                    <span class="font-mono text-blue-600 dark:text-blue-400" x-text="defaultBaseUrl || '—'"></span>
                </p>
            </div>

            {{-- API Key --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    API Key <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showApiKey ? 'text' : 'password'" x-model="form.api_key" required
                           placeholder="sk-xxxx... หรือ gsk_xxxx..."
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
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">API Key จะถูกเข้ารหัสก่อนบันทึก</p>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                <input type="number" x-model="form.priority" min="0" max="100"
                       placeholder="0-100 (สูง = ใช้ก่อน)"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ค่า priority สูงจะถูกเลือกใช้ก่อนในโหมด Priority และ Failover</p>
            </div>

            {{-- 🎯 (2026-05-02) Purpose — จำกัดการใช้ key ตามวัตถุประสงค์ --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🎯 วัตถุประสงค์ (Purpose)
                </label>
                <select x-model="form.purpose"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    {{-- 🎯 (2026-05-08) ใช้ AiApiKey::PURPOSES const — ครอบคลุม any/prediction/free_card/chat/sensitive/tts
                         เดิม hardcode 4 options ขาด sensitive (Sensitive AI Mode) + tts (TTS providers) --}}
                    @foreach(\App\Models\AiApiKey::PURPOSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    📋 <strong>Hierarchy การเลือก key</strong> (เจาะจง → ทั่วไป):<br>
                    💎 <strong>prediction_celtic</strong> → Celtic 99฿ ก่อน, fallback → prediction → any<br>
                    🌟 <strong>prediction_deep</strong> → Deep 39฿ ก่อน, fallback → prediction → any<br>
                    🔮 <strong>prediction</strong> = legacy — ใช้ทั้ง Deep + Celtic (ถ้าไม่อยากแยก)<br>
                    🎁 <strong>free_card</strong> → ทำนายฟรี (boost +1000 — สงวน paid keys)<br>
                    💬 <strong>chat</strong> → แชทสนทนา (FREE) — ไม่กระทบ pool ทำนาย<br>
                    <span class="text-blue-600 dark:text-blue-400">💙 <strong>chat_paid</strong> → แชทพิเศษ (สีฟ้า, paid) — LAST RESORT หลัง free + any หมด</span><br>
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
                          placeholder="หมายเหตุเพิ่มเติม..."
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" :disabled="submitting"
                        class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition flex items-center justify-center gap-2">
                    <svg x-show="submitting" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึก API Key'"></span>
                </button>
                <a href="{{ route('admin.ai-api-keys.index') }}"
                   class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function createApiKey() {
    return {
        showApiKey: false,
        submitting: false,
        // 🎯 (2026-05-01) provider → models map (cascading dropdown)
        modelsByProvider: @json($modelsByProvider),
        defaultBaseUrls: @json($defaultBaseUrls),
        availableModels: [],
        defaultBaseUrl: '',
        form: {
            name: '',
            provider: '{{ $provider ?? '' }}',
            model: '',
            base_url: '',
            api_key: '',
            priority: 0,
            purpose: 'any',
            tokens_limit_daily: '',
            tokens_limit_monthly: '',
            rate_limit_per_minute: '',
            notes: ''
        },

        init() {
            this.onProviderChange();
        },

        onProviderChange() {
            const p = this.form.provider;
            this.availableModels = this.modelsByProvider[p] || [];
            this.defaultBaseUrl = this.defaultBaseUrls[p] || '';
            // ถ้า model ที่เลือกอยู่ ไม่อยู่ใน list ใหม่ → reset
            if (this.form.model && !this.availableModels.includes(this.form.model)) {
                this.form.model = '';
            }
        },

        async submitForm() {
            if (!this.form.name || !this.form.provider || !this.form.api_key) {
                alert('กรุณากรอกข้อมูลที่จำเป็น');
                return;
            }

            this.submitting = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = '{{ route('admin.ai-api-keys.provider', '') }}/' + this.form.provider;
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
